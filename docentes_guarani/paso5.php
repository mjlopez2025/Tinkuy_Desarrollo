<?php
// =============================================
// SCRIPT PARA ACTUALIZAR DATOS DE DOCENTES
// =============================================
echo "\nIniciando actualización de datos de docentes...\n";

try {
    // 1. Conexión a la base de datos (asegúrate de que $conn ya está configurado)
    
    // 2. Contar registros totales a procesar
    $total_registros = $conn->query("SELECT COUNT(*) FROM Docentes_Guarani WHERE docente_guarani IS NOT NULL")->fetchColumn();
    echo "Total de registros en la tabla: $total_registros\n";
    
    $registros_a_procesar = $conn->query("SELECT COUNT(*) FROM Docentes_Guarani WHERE docente_guarani IS NOT NULL AND (tipo_doc_guarani IS NULL OR num_doc_guarani IS NULL)")->fetchColumn();
    echo "Registros a procesar: $registros_a_procesar\n";

    // 3. Seleccionar registros para procesar
    $registros = $conn->query("
        SELECT id, docente_guarani 
        FROM Docentes_Guarani 
        WHERE docente_guarani IS NOT NULL
          AND (tipo_doc_guarani IS NULL OR num_doc_guarani IS NULL)
    ");

    $procesados = 0;
    $omitidos = 0;
    $errores = 0;
    $registros_modificados = []; // Array para almacenar los registros modificados

    // 4. Procesar cada registro
    foreach ($registros->fetchAll(PDO::FETCH_ASSOC) as $registro) {
        $id = $registro['id'];
        $docente_raw = trim($registro['docente_guarani']);
        
        $output = "\nProcesando ID $id: $docente_raw";
        
        // Patrón para extraer datos
        if (preg_match('/^([^,]+?)\s*[,|-]\s*([^,]+?)\s*[,|-]\s*([^,]+)$/', $docente_raw, $matches)) {
            $nombre_completo = trim($matches[1]);
            $tipo_doc_guarani = trim($matches[2]);
            $num_doc_guarani = trim($matches[3]);
            
            $output .= "\nDatos extraídos:";
            $output .= "\n- Nombre completo: $nombre_completo";
            $output .= "\n- Tipo documento: $tipo_doc_guarani";
            $output .= "\n- Número documento: $num_doc_guarani";
            
            try {
                // Actualizar los campos en la base de datos
                $stmt = $conn->prepare("
                    UPDATE Docentes_Guarani
                    SET 
                        docente_guarani = ?,
                        tipo_doc_guarani = ?,
                        num_doc_guarani = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $nombre_completo,
                    $tipo_doc_guarani,
                    $num_doc_guarani,
                    $id
                ]);
                
                $output .= "\n✅ Actualización exitosa";
                $procesados++;
                $registros_modificados[] = $output; // Almacenamos el output para mostrarlo al final
                
            } catch (PDOException $e) {
                $output .= "\n🚨 Error al actualizar ID $id: " . $e->getMessage();
                $errores++;
                echo $output;
            }
            
        } else {
            $output .= "\n⚠️ Formato no reconocido - No se actualizará";
            $omitidos++;
            echo $output;
        }
        
        echo "\n" . str_repeat("-", 60);
    }

    // 5. Mostrar resumen final
    echo "\n\nRESUMEN FINAL:";
    echo "\n✔️ Registros procesados correctamente: $procesados";
    echo "\n⚠️ Registros omitidos (formato no reconocido): $omitidos";
    echo "\n❌ Errores en actualización: $errores";
    echo "\n🎉 Proceso completado.\n";

    // =============================================
    // SCRIPT PARA LIMPIAR "TITULAR/ADJUNTO" EN REGISTROS ESPECÍFICOS
    // =============================================
    echo "\n\nIniciando limpieza de prefijos 'Titular/Adjunto'...\n";

    $registros_a_limpiar = [
        2865 => "Titular, CAPURRO Antonela, DNI, 33104900",
        2905 => "Titular, CAPURRO Antonela, DNI, 33104900",
        5151 => "Titular, BALDACCHINO Pablo Gastón, DNI, 25226342",
        6364 => "Titular, DIAS Adrian Eduardo, DNI, 29174078",
        6404 => "Titular, DIAS Adrian Eduardo, DNI, 29174078",
        7536 => "Adjunto, QUARTINO BAZA Antonio, DNI, 22667880"
    ];

    $procesados_limpieza = 0;
    $registros_limpieza_modificados = []; // Array para almacenar los registros modificados en la limpieza

    try {
        $conn->beginTransaction();
        
        foreach ($registros_a_limpiar as $id => $valor_actual) {
            $nuevo_valor = preg_replace('/^(Titular|Adjunto),\s*/i', '', $valor_actual);
            
            if ($nuevo_valor !== $valor_actual) {
                $stmt = $conn->prepare("
                    UPDATE Docentes_Guarani 
                    SET docente_guarani = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nuevo_valor, $id]);
                
                $output = "\n✅ ID $id ACTUALIZADO:";
                $output .= "\n   ANTES: '$valor_actual'";
                $output .= "\n   DESPUÉS: '$nuevo_valor'";
                $procesados_limpieza++;
                $registros_limpieza_modificados[] = $output;
            }
        }
        
        $conn->commit();
        echo "\n\n🎉 RESULTADO LIMPIEZA:";
        echo "\n- Registros procesados: " . count($registros_a_limpiar);
        echo "\n- Registros modificados: $procesados_limpieza";
        echo "\n[Transacción confirmada]\n";
        
    } catch (PDOException $e) {
        $conn->rollBack();
        echo "\n🚨 ERROR EN LIMPIEZA: " . $e->getMessage();
        echo "\n[Transacción revertida]\n";
    }

    // =============================================
    // MOSTRAR REGISTROS MODIFICADOS AL FINAL
    // =============================================
    if (!empty($registros_modificados)) {
        echo "\n\nREGISTROS MODIFICADOS EN EL PRIMER PROCESO:\n";
        echo str_repeat("=", 60) . "\n";
        foreach ($registros_modificados as $output) {
            echo $output . "\n";
            echo str_repeat("-", 60) . "\n";
        }
    }

    if (!empty($registros_limpieza_modificados)) {
        echo "\n\nREGISTROS MODIFICADOS EN LA LIMPIEZA:\n";
        echo str_repeat("=", 60) . "\n";
        foreach ($registros_limpieza_modificados as $output) {
            echo $output . "\n";
            echo str_repeat("-", 60) . "\n";
        }
    }

} catch (PDOException $e) {
    echo "\n🚨 Error en la base de datos: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "\n🚨 Error general: " . $e->getMessage() . "\n";
}

// =============================================
// SCRIPT PARA LIMPIAR Y ELIMINAR COLUMNAS ADICIONALES (POSTGRESQL)
// =============================================
echo "\n\nIniciando limpieza y eliminación de columnas adicionales...\n";

try {
    // Iniciar transacción para seguridad
    $conn->beginTransaction();
    
    // Columnas a procesar
    $columnas = [
        'ape_nom1_guarani', 'tipo_doc1_guarani', 'num_doc1_guarani',
        'ape_nom2_guarani', 'tipo_doc2_guarani', 'num_doc2_guarani',
        'ape_nom3_guarani', 'tipo_doc3_guarani', 'num_doc3_guarani',
        'ape_nom4_guarani', 'tipo_doc4_guarani', 'num_doc4_guarani',
        'ape_nom5_guarani', 'tipo_doc5_guarani', 'num_doc5_guarani'
    ];
    
    $total_limpiados = 0;
    $total_eliminados = 0;
    
    foreach ($columnas as $columna) {
        // Verificar si la columna existe
        $existe = $conn->query("
            SELECT EXISTS (
                SELECT 1 
                FROM information_schema.columns 
                WHERE table_name = 'docentes_guarani' 
                AND column_name = '$columna'
            )"
        )->fetchColumn();
        
        if ($existe) {
            // 1. Limpiar columna (establecer NULL)
            $conn->exec("UPDATE Docentes_Guarani SET $columna = NULL");
            $afectados = $conn->query("SELECT COUNT(*) FROM Docentes_Guarani WHERE $columna IS NOT NULL")->fetchColumn();
            
            // 2. Eliminar columna
            $conn->exec("ALTER TABLE Docentes_Guarani DROP COLUMN $columna");
            
            echo "\n✔️ Columna '$columna':";
            echo "\n   - Limpiada (registros afectados: $afectados)";
            echo "\n   - Eliminada permanentemente";
            
            $total_limpiados += $afectados;
            $total_eliminados++;
        } else {
            echo "\n⚠️ Columna '$columna' no existe en la tabla, se omite";
        }
    }
    
    // Confirmar cambios
    $conn->commit();
    
    echo "\n\n🎉 PROCESO COMPLETADO:";
    echo "\n- Columnas limpiadas: " . count($columnas);
    echo "\n- Columnas eliminadas: $total_eliminados";
    echo "\n- Registros afectados: $total_limpiados";
    echo "\n[Transacción confirmada]\n";
    
} catch (PDOException $e) {
    $conn->rollBack();
    echo "\n🚨 ERROR DURANTE EL PROCESO: " . $e->getMessage();
    echo "\n[Transacción revertida - Ningún cambio aplicado]\n";
}