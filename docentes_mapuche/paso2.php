<?php
include_once("../config.php");

// 1. Definir el esquema a usar (sin modificar config.php)
$esquema = 'mapuche'; // Definición directa para este script

// 2. Verificación inicial de conexiones
if (!isset($conn_wichi) || !($conn_wichi instanceof PDO)) {
    die("❌ Error: Conexión a Wichi no está disponible o no es válida");
}

if (!isset($conn) || !($conn instanceof PDO)) {
    die("❌ Error: Conexión a la base de datos local no está disponible o no es válida");
}

try {
    // 3. Verificar existencia de tablas en Wichi (usando $esquema)
    echo "🔍 Verificando tablas requeridas en esquema $esquema...\n";
    $tablasRequeridas = ['ft_personal_cerrada', 'd_categoriascargo', 'd_persona'];
    
    foreach ($tablasRequeridas as $tabla) {
        $query = $conn_wichi->prepare("SELECT to_regclass(:esquema || '.' || :tabla)");
        $query->execute([
            ':esquema' => $esquema,  // Usamos la variable local
            ':tabla' => $tabla
        ]);
        $exists = $query->fetchColumn();
        
        if (!$exists) {
            throw new PDOException("Tabla requerida no encontrada: $esquema.$tabla");
        }
    }
    echo "✅ Todas las tablas requeridas existen\n";

    // 4. Consulta OLAP optimizada (con esquema dinámico)
    echo "⚙️ Ejecutando consulta OLAP...\n";
    $olapQuery = "
    WITH sql_olap_ft AS (
        SELECT 
            p.persona_id, 
            p.categoria_id, 
            p.nro_cargo, 
            p.estadodelcargo_id, 
            p.dependenciadesigncargo_id, 
            p.anio_id, 
            p.mes_id,
            p.persona_id AS medida_persona_id 
        FROM 
            $esquema.ft_personal_cerrada p
        INNER JOIN 
            $esquema.d_categoriascargo c ON c.categoria_id = p.categoria_id 
        WHERE 
            c.escalafon_desc = 'Docente'
        GROUP BY 
            p.persona_id, p.categoria_id, p.nro_cargo, p.estadodelcargo_id, 
            p.dependenciadesigncargo_id, p.anio_id, p.mes_id
    ) 
    SELECT 
        per.apellidonombre_desc,
        per.nro_documento,
        cat.categoria_desc,
        ft.nro_cargo,
        cat.dedicacion_desc,
        est.estadodelcargo_desc,
        dep.dependenciadesign_desc,
        anio.anio_id,
        mes.mes_desc,
        COUNT(DISTINCT ft.medida_persona_id) AS persona_id
    FROM 
        sql_olap_ft ft
    INNER JOIN $esquema.d_persona per ON per.persona_id = ft.persona_id
    INNER JOIN $esquema.d_categoriascargo cat ON cat.categoria_id = ft.categoria_id
    INNER JOIN $esquema.d_estadodelcargo est ON est.estadodelcargo_id = ft.estadodelcargo_id
    INNER JOIN $esquema.d_dependenciadesig dep ON dep.dependenciadesign_id = ft.dependenciadesigncargo_id
    INNER JOIN public.d_anio anio ON anio.anio_id = ft.anio_id
    INNER JOIN public.d_mes mes ON mes.mes_id = ft.mes_id
    GROUP BY 
        per.apellidonombre_desc, per.nro_documento, cat.categoria_desc, ft.nro_cargo,
        cat.dedicacion_desc, est.estadodelcargo_desc, dep.dependenciadesign_desc,
        anio.anio_id, mes.mes_desc
    ORDER BY 
        per.apellidonombre_desc, per.nro_documento, cat.categoria_desc, ft.nro_cargo
    ";

    $localStmt = $conn_wichi->query($olapQuery);
    if (!$localStmt) {
        throw new PDOException("Error al ejecutar consulta OLAP: " . implode(" ", $conn_wichi->errorInfo()));
    }

    // 5. Preparar inserción en la base local
    $insertQuery = "
    INSERT INTO docentes_mapuche (
        apellidonombre_desc, nro_documento, categoria_desc, nro_cargo,
        dedicacion_desc, estadodelcargo_desc, dependenciadesign_desc,
        anio_id, mes_desc, persona_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $remoteStmt = $conn->prepare($insertQuery);
    if (!$remoteStmt) {
        throw new PDOException("Error al preparar consulta de inserción: " . implode(" ", $conn->errorInfo()));
    }

    // 6. Procesamiento por lotes con transacción
    echo "⏳ Iniciando transferencia de datos...\n";
    
    // Limpiar tabla destino
    $conn->beginTransaction();
    $conn->exec("TRUNCATE TABLE docentes_mapuche");
    
    $totalRegistros = 0;
    $batchSize = 100;
    $batchCount = 0;

    while ($row = $localStmt->fetch(PDO::FETCH_ASSOC)) {
        $remoteStmt->execute([
            $row['apellidonombre_desc'] ?? null,
            $row['nro_documento'] ?? null,
            $row['categoria_desc'] ?? null,
            $row['nro_cargo'] ?? null,
            $row['dedicacion_desc'] ?? null,
            $row['estadodelcargo_desc'] ?? null,
            $row['dependenciadesign_desc'] ?? null,
            $row['anio_id'] ?? null,
            $row['mes_desc'] ?? null,
            $row['persona_id'] ?? null
        ]);
        
        $totalRegistros++;
        $batchCount++;
        
        if ($batchCount >= $batchSize) {
            echo "📦 Lote transferido: $totalRegistros registros\n";
            $batchCount = 0;
        }
    }

    $conn->commit();
    echo "\n✅ Transferencia completada. Total registros: $totalRegistros\n";

    // 7. Verificación final
    $count = $conn->query("SELECT COUNT(*) FROM docentes_mapuche")->fetchColumn();
    if ($count != $totalRegistros) {
        echo "⚠️ Advertencia: El conteo final ($count) no coincide con los registros transferidos ($totalRegistros)\n";
    } else {
        echo "📊 Verificación exitosa: $count registros en tabla destino\n";
    }

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
        echo "🔙 Se revirtió la transacción debido a un error\n";
    }
    
    echo "\n❌ Error crítico: " . $e->getMessage() . "\n";
    echo "Código de error: " . $e->getCode() . "\n";
    
    if (isset($olapQuery)) {
        error_log("Consulta fallida:\n" . $olapQuery);
    }
    
    exit(1);
} finally {
    if (isset($localStmt)) $localStmt = null;
    if (isset($remoteStmt)) $remoteStmt = null;
    if (isset($conn_wichi)) $conn_wichi = null;
    if (isset($conn)) $conn = null;
    
    echo "\n🔌 Conexiones cerradas correctamente\n";
}
?>