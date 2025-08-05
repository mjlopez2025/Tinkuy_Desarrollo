<?php
include_once("../config.php");

try {
    // Conexión a la base Wichi (solo para leer datos)
    $conn_wichi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Conexión a la base local (para guardar datos)
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Consulta de datos desde Wichi
    $sql_query = "WITH sql_olap_ft AS (
    SELECT 
        ft_rendimiento.responsable_academica_id, 
        ft_rendimiento.propuesta_formativa_id, 
        ft_rendimiento.comision_id, 
        ft_rendimiento.periodo_id, 
        ft_rendimiento.docente_id, 
        ft_rendimiento.actividad_id, 
        d_anio.anio_id, 
        sum(ft_rendimiento.cantidad_registros_cursada) AS medida_cantidad_registros_cursada 
    FROM 
        (SELECT * FROM guarani3.ft_rendimiento) ft_rendimiento 
    INNER JOIN 
        public.d_anio ON d_anio.anio_id = ft_rendimiento.anio_academico 
    WHERE 
        (d_anio.anio_id = '2025') AND  -- Cambiado de 2024 a 2025
        (ft_rendimiento.cantidad_registros_cursada is not null) 
    GROUP BY 
        ft_rendimiento.responsable_academica_id,
        ft_rendimiento.propuesta_formativa_id,
        ft_rendimiento.comision_id,
        ft_rendimiento.periodo_id,
        ft_rendimiento.docente_id,
        ft_rendimiento.actividad_id,
        d_anio.anio_id 
    ORDER BY 
        ft_rendimiento.responsable_academica_id,
        ft_rendimiento.propuesta_formativa_id,
        ft_rendimiento.comision_id,
        ft_rendimiento.periodo_id,
        ft_rendimiento.docente_id,
        ft_rendimiento.actividad_id,
        d_anio.anio_id
) 
SELECT 
    d_responsable_academica.responsable_academica_desc, 
    d_propuesta_formativa.propuesta_formativa_desc, 
    d_comision.comision_desc, 
    v_periodo_anio_academico_x_tipo_periodo.periodo_desc, 
    d_docente.docente_desc, 
    d_actividad.actividad_desc, 
    sql_olap_ft.anio_id, 
    sum(sql_olap_ft.medida_cantidad_registros_cursada) AS cantidad_registros_cursada 
FROM 
    sql_olap_ft 
INNER JOIN 
    guarani3.d_responsable_academica d_responsable_academica ON d_responsable_academica.responsable_academica_id = sql_olap_ft.responsable_academica_id 
INNER JOIN 
    guarani3.d_propuesta_formativa d_propuesta_formativa ON d_propuesta_formativa.propuesta_formativa_id = sql_olap_ft.propuesta_formativa_id 
INNER JOIN 
    guarani3.d_comision d_comision ON d_comision.comision_id = sql_olap_ft.comision_id 
INNER JOIN 
    guarani3.v_periodo_anio_academico_x_tipo_periodo v_periodo_anio_academico_x_tipo_periodo ON v_periodo_anio_academico_x_tipo_periodo.periodo_id = sql_olap_ft.periodo_id 
INNER JOIN 
    guarani3.d_docente d_docente ON d_docente.docente_id = sql_olap_ft.docente_id 
INNER JOIN 
    guarani3.d_actividad d_actividad ON d_actividad.actividad_id = sql_olap_ft.actividad_id 
WHERE 
    (true) 
GROUP BY 
    d_responsable_academica.responsable_academica_desc,
    d_propuesta_formativa.propuesta_formativa_desc,
    d_comision.comision_desc,
    v_periodo_anio_academico_x_tipo_periodo.periodo_desc,
    d_docente.docente_desc,
    d_actividad.actividad_desc,
    sql_olap_ft.anio_id 
ORDER BY 
    d_responsable_academica.responsable_academica_desc,
    d_propuesta_formativa.propuesta_formativa_desc,
    d_comision.comision_desc,
    v_periodo_anio_academico_x_tipo_periodo.periodo_desc,
    d_docente.docente_desc,
    d_actividad.actividad_desc,
    sql_olap_ft.anio_id";
    
    $stmt_query = $conn_wichi->prepare($sql_query);
    $stmt_query->execute();
    $resultados = $stmt_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Consulta preparada para insertar en la base local
    $sql_insert = "INSERT INTO Docentes_Guarani (
    responsabilidad_academica_guarani, 
    propuesta_formativa_guarani, 
    comision_guarani, 
    periodo_guarani, 
    anio_guarani,
    docente_guarani, 
    tipo_doc_guarani,
    num_doc_guarani,
    actividad_guarani, 
    cursados_guarani,
    ape_nom1_Guarani,
    tipo_doc1_Guarani,
    num_doc1_Guarani,
    ape_nom2_Guarani,
    tipo_doc2_Guarani,
    num_doc2_Guarani,
    ape_nom3_Guarani,
    tipo_doc3_Guarani,
    num_doc3_Guarani,
    ape_nom4_Guarani,
    tipo_doc4_Guarani,
    num_doc4_Guarani
) VALUES (
    :responsabilidad_academica, 
    :propuesta_formativa, 
    :comision, 
    :periodo, 
    :anio,
    :docente, 
    :tipo_doc_guarani,
    :num_doc_guarani,
    :actividad, 
    :cursados,
    :ape_nom1_Guarani,
    :tipo_doc1_Guarani,
    :num_doc1_Guarani,
    :ape_nom2_Guarani,
    :tipo_doc2_Guarani,
    :num_doc2_Guarani,
    :ape_nom3_Guarani,
    :tipo_doc3_Guarani,
    :num_doc3_Guarani,
    :ape_nom4_Guarani,
    :tipo_doc4_Guarani,
    :num_doc4_Guarani
)";


    $stmt = $conn->prepare($sql_insert);
    $importedCount = 0;

    echo "===========================================================\n";
    echo "Paso 2. Iniciando importación desde datos de consulta SQL...\n";
    echo "===========================================================\n";

    foreach ($resultados as $fila) {
        $responsabilidad_academica = trim($fila['responsable_academica_desc']);
        $propuesta_formativa = trim($fila['propuesta_formativa_desc']);
        $comision = trim($fila['comision_desc']);
        $periodo = trim($fila['periodo_desc']);
        $anio = substr($periodo, 0, 4); // Extraer el año
        $docente = trim($fila['docente_desc']);
        $actividad = trim($fila['actividad_desc']);
        $cursados = trim($fila['cantidad_registros_cursada']);

        $stmt->bindParam(':responsabilidad_academica', $responsabilidad_academica);
        $stmt->bindParam(':propuesta_formativa', $propuesta_formativa);
        $stmt->bindParam(':comision', $comision);
        $stmt->bindParam(':periodo', $periodo);
        $stmt->bindParam(':anio', $anio);
        $stmt->bindParam(':docente', $docente);
        $stmt->bindParam(':actividad', $actividad);
        $stmt->bindParam(':cursados', $cursados);

        // Separar tipo y número de documento del primer docente (si hay alguno)
$primer_docente = $docentes[0] ?? null;
$tipo_doc_guarani = null;
$num_doc_guarani = null;

if ($primer_docente) {
    $datos = array_map('trim', explode(',', $primer_docente));
    if (count($datos) >= 3) {
        $tipo_doc_guarani = $datos[1] !== '' ? $datos[1] : null;
        $num_doc_guarani = is_numeric($datos[2]) ? (int)$datos[2] : null;
    }
}

$stmt->bindValue(':tipo_doc_guarani', $tipo_doc_guarani, PDO::PARAM_STR);
$stmt->bindValue(':num_doc_guarani', $num_doc_guarani, is_null($num_doc_guarani) ? PDO::PARAM_NULL : PDO::PARAM_INT);


        $docentes = explode('-', $docente);
        $docentes = array_map('trim', $docentes);

        $docentesParams = [];
        for ($i = 1; $i <= 4; $i++) {
            $docentesParams["ape_nom{$i}_Guarani"] = '';
            $docentesParams["tipo_doc{$i}_Guarani"] = '';
            $docentesParams["num_doc{$i}_Guarani"] = '';
        }

        foreach ($docentes as $index => $docenteStr) {
            $docenteData = array_map('trim', explode(',', $docenteStr));
            if (count($docenteData) >= 3) {
                $i = $index + 1;
                if ($i <= 4) {
                    $docentesParams["ape_nom{$i}_Guarani"] = $docenteData[0];
                    $docentesParams["tipo_doc{$i}_Guarani"] = $docenteData[1];
                    $docentesParams["num_doc{$i}_Guarani"] = $docenteData[2];
                }
            }
        }

        foreach ($docentesParams as $param => $value) {
            $stmt->bindValue(":{$param}", $value);
        }

        $stmt->execute();
        $importedCount++;
    }

    echo "\nImportación completada con éxito.\n";
    echo "Total de registros obtenidos: " . count($resultados) . "\n";
    echo "Total de registros importados: $importedCount\n";

} catch (PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage() . "\n";
    echo "Consulta que falló: " . (isset($sql_insert) ? $sql_insert : $sql_query) . "\n";
} catch (Exception $e) {
    echo "Error general: " . $e->getMessage() . "\n";
}
