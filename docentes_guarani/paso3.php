<?php

echo "==========================================================================\n";
echo "\nPaso 3. Separando registros con múltiples docentes y extrayendo datos...\n";
echo "==========================================================================\n";

try {
    // 1. Contar registros iniciales (para comparar después)
    $total_inicial = $conn->query("SELECT COUNT(*) FROM Docentes_Guarani")->fetchColumn();
    echo "Registros iniciales: $total_inicial\n";

    // 2. Identificar registros con múltiples docentes (separados por " - ")
    $registros = $conn->query("
        SELECT * FROM Docentes_Guarani 
        WHERE docente_guarani LIKE '% - %'
    ");

    // 3. Procesar cada registro
    foreach ($registros->fetchAll(PDO::FETCH_ASSOC) as $registro) {
        // Separar docentes (usando " - " como delimitador)
        $docentes = explode(" - ", $registro['docente_guarani']);

        // === Actualizar el primer docente en el registro original ===
        $primero = explode(",", $docentes[0]);
        $nombre1 = preg_replace('/^[\.\-,\s]+/', '', trim($primero[0] ?? null));
        $tipo1 = trim($primero[1] ?? null);
        $doc1   = trim($primero[2] ?? null);

        $docente_limpio = preg_replace('/^[\.\-,\s]+/', '', trim($docentes[0]));

        $conn->prepare("
            UPDATE Docentes_Guarani 
            SET docente_guarani = ?, ape_nom1_guarani = ?, tipo_doc1_guarani = ?, num_doc1_guarani = ?
            WHERE id = ?
        ")->execute([
            $docente_limpio, $nombre1, $tipo1, $doc1,
            $registro['id']
        ]);

        // === Insertar docentes adicionales como nuevos registros ===
        for ($i = 1; $i < count($docentes); $i++) {
            // Clonar el registro original
            $nuevoRegistro = $registro;

            // Eliminar el ID y datos del primer docente
            unset(
                $nuevoRegistro['id'],
                $nuevoRegistro['ape_nom1_guarani'],
                $nuevoRegistro['tipo_doc1_guarani'],
                $nuevoRegistro['num_doc1_guarani']
            );

            // Datos del docente adicional
            $partes = explode(",", $docentes[$i]);
            $nombre = preg_replace('/^[\.\-,\s]+/', '', trim($partes[0] ?? null));
            $tipo_doc = trim($partes[1] ?? null);
            $num_doc = trim($partes[2] ?? null);

            // Agregar los nuevos datos del docente
            $nuevoRegistro['docente_guarani'] = preg_replace('/^[\.\-,\s]+/', '', trim($docentes[$i]));
            $nuevoRegistro['ape_nom1_guarani'] = $nombre;
            $nuevoRegistro['tipo_doc1_guarani'] = $tipo_doc;
            $nuevoRegistro['num_doc1_guarani'] = $num_doc;

            // Construir la consulta dinámica
            $campos = implode(", ", array_keys($nuevoRegistro));
            $placeholders = implode(", ", array_fill(0, count($nuevoRegistro), "?"));

            $conn->prepare("
                INSERT INTO Docentes_Guarani ($campos) 
                VALUES ($placeholders)
            ")->execute(array_values($nuevoRegistro));
        }
    }

    // 4. Verificación final
    $total_final = $conn->query("SELECT COUNT(*) FROM Docentes_Guarani")->fetchColumn();
    $nuevos_registros = $total_final - $total_inicial;

    echo "✅ ¡Proceso completado!\n";
    echo "Registros iniciales: $total_inicial\n";
    echo "Registros nuevos creados: $nuevos_registros\n";
    echo "Total de registros ahora: $total_final\n\n";

} catch (PDOException $e) {
    echo "\n🚨 Error: " . $e->getMessage() . "\n";
}
