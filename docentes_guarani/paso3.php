<?php
include_once("../config.php");

// =========================================
    // SCRIPT PARA LIMPIAR "TITULAR/ADJUNTO"
    // =====================================
echo "\n\nIniciando limpieza de prefijos 'Titular,' y 'Adjunto,' en todos los registros...\n";

$tabla = 'docentes_guarani';         
$campo = 'docente_guarani';      

try {
    // Buscar registros que comienzan con 'Titular,' o 'Adjunto,'
    $query = "
        SELECT id, $campo 
        FROM $tabla 
        WHERE $campo ILIKE 'Titular,%' OR $campo ILIKE 'Adjunto,%'
    ";
    $stmt = $conn->query($query);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Registros encontrados para limpieza: " . count($registros) . "\n";

    $procesados = 0;
    $modificados = [];

    foreach ($registros as $registro) {
        $id = $registro['id'];
        $texto_original = $registro[$campo];

        // Limpiar el prefijo si existe
        $nuevo_texto = preg_replace('/^(Titular|Adjunto|Adjunto,),\s*/i', '', $texto_original);

        if ($nuevo_texto !== $texto_original) {
            $update = $conn->prepare("UPDATE $tabla SET $campo = :nuevo_texto WHERE id = :id");
            $update->execute([
                ':nuevo_texto' => $nuevo_texto,
                ':id' => $id
            ]);

            $procesados++;
            $modificados[] = [
                'id' => $id,
                'antes' => $texto_original,
                'después' => $nuevo_texto
            ];
        }
    }

    echo "Total de registros modificados: $procesados\n";

    // Mostrar algunos registros modificados como ejemplo
    foreach (array_slice($modificados, 0, 10) as $mod) {
        echo "ID {$mod['id']}: '{$mod['antes']}' → '{$mod['después']}'\n";
    }

    if (count($modificados) > 10) {
        echo "...\nMostrando solo los primeros 10 registros modificados.\n";
    }

} catch (PDOException $e) {
    echo "Error al ejecutar limpieza: " . $e->getMessage() . "\n";
}
