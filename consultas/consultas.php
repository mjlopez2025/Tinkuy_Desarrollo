<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *"); // Permite cualquier dominio (¡solo para desarrollo!)
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

include_once("../config.php");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class ConsultasDocentes {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // ================================
    // 1. DOCENTES COMBINADOS 
    // ================================
    public function docentesCombinados($page = 1, $perPage = 50, $searchTerm = '', $year = null) {
    $offset = ($page - 1) * $perPage;
    $whereClause = '';
    $params = [];

    // Filtro por búsqueda
    if (!empty($searchTerm)) {
        $whereClause .= " AND m.apellidonombre_desc ILIKE :searchTerm";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    // Filtro por año
    if ($year !== null) {
        $whereClause .= " AND (m.anio_id = :year OR g.anio_guarani = :year)";
        $params[':year'] = $year;
    }

    $sql = "SELECT 
        COALESCE(m.apellidonombre_desc, 'Sin información') AS \"Apellido Nombre (M)\",
        COALESCE(m.nro_documento::TEXT, 'Sin información') AS \"Doc (M)\",
        COALESCE(m.categoria_desc, 'Sin información') AS \"Cat (M)\", 
        COALESCE(m.nro_cargo::TEXT, 'Sin información') AS \"Cargo (M)\",
        COALESCE(m.dedicacion_desc, 'Sin información') AS \"Dedicacion(M)\",
        COALESCE(m.estadodelcargo_desc, 'Sin información') AS \"Estado (M)\", 
        COALESCE(m.dependenciadesign_desc, 'Sin información') AS \"Dpto (M)\",
        COALESCE(g.responsabilidad_academica_guarani, 'Sin información') AS \"Resp Acad (G)\",
        COALESCE(g.propuesta_formativa_guarani, 'Sin información') AS \"Propuesta (G)\", 
        COALESCE(g.comision_guarani, 'Sin información') AS \"Com (G)\",
        COALESCE(g.anio_guarani::TEXT, 'Sin información') AS \"Año( G)\",
        COALESCE(g.periodo_guarani, 'Sin información') AS \"Périodo (G)\",
        COALESCE(g.actividad_guarani, 'Sin información') AS \"Actividad( G)\",
        COALESCE(g.cursados_guarani, 'Sin información') AS \"Est (G)\"
    FROM 
        docentes_mapuche AS m
    LEFT JOIN 
        docentes_guarani AS g 
        ON m.nro_documento::VARCHAR = g.num_doc_guarani
    WHERE 
        (g.num_doc_guarani IS NULL OR 
        (m.categoria_desc <> g.propuesta_formativa_guarani OR
         m.dedicacion_desc <> g.actividad_guarani))
        $whereClause
    GROUP BY
        m.apellidonombre_desc, m.nro_documento, m.categoria_desc, m.nro_cargo,
        m.dedicacion_desc, m.estadodelcargo_desc, m.dependenciadesign_desc,
        g.responsabilidad_academica_guarani, g.propuesta_formativa_guarani,
        g.comision_guarani, g.anio_guarani, g.periodo_guarani,
        g.actividad_guarani, g.cursados_guarani
    ORDER BY 
        m.apellidonombre_desc
    LIMIT :limit OFFSET :offset";

    $stmt = $this->conn->prepare($sql);

    // Bind dinámico de parámetros
    foreach ($params as $key => $value) {
        if ($key === ':year') {
            $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
    }

    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();

    return [
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $this->contarDocentesCombinados($searchTerm, $year)
    ];
}


    public function contarDocentesCombinados($searchTerm = '', $year = '') 
{
    $whereClause = '';
    $params = [];

    if (!empty($searchTerm)) {
        $whereClause = " AND m.apellidonombre_desc ILIKE :searchTerm";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    if ($year !== 'all') {
        $whereClause .= " AND (m.anio_id = :year OR g.anio_guarani = :year)";
        $params[':year'] = $year;
    }

    // CONSULTA CORREGIDA:
    $sql = "SELECT COUNT(*) as total
            FROM docentes_mapuche AS m
            LEFT JOIN docentes_guarani AS g 
            ON m.nro_documento::VARCHAR = g.num_doc_guarani
            WHERE (g.num_doc_guarani IS NULL OR 
                  (m.categoria_desc <> g.propuesta_formativa_guarani OR
                   m.dedicacion_desc <> g.actividad_guarani))
            $whereClause";

    $stmt = $this->conn->prepare($sql);
    
    if (!empty($searchTerm)) {
        $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
    }
    
    if ($year !== 'all') {
        $stmt->bindValue(':year', $year);
    }

    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return (int)$result['total'];
}

    // ================================
    // 2. DOCENTES MAPUCHE 
    // ================================
    public function obtenerDocentesMapuche($page = 1, $perPage = 50, $searchTerm = '', $year = '') {
    $offset = ($page - 1) * $perPage;
    $whereClause = '';
    $params = [];

    if (!empty($searchTerm)) {
        $whereClause = " WHERE apellidonombre_desc ILIKE :searchTerm";
        $params[':searchTerm'] = '%' . $searchTerm . '%';
    }

    // Consulta principal con DISTINCT ON para evitar duplicados
    $sql = "SELECT 
        DISTINCT ON (
            apellidonombre_desc, 
            nro_documento,
            categoria_desc,
            nro_cargo
        )
        COALESCE(apellidonombre_desc, 'Sin información') AS \"Apellido y Nombre\",
        COALESCE(nro_documento::TEXT, 'Sin información') AS \"Num. Doc.\",
        COALESCE(categoria_desc, 'Sin información') AS \"EstCategoria\",
        COALESCE(nro_cargo::TEXT, 'Sin información') AS \"Cargo\",
        COALESCE(dedicacion_desc, 'Sin información') AS \"Dedicación\",
        COALESCE(estadodelcargo_desc, 'Sin información') AS \"Estado Cargo\",
        COALESCE(dependenciadesign_desc, 'Sin información') AS \"Dependencia\",
        COALESCE(anio_id::TEXT, 'Sin información') AS \"Año\"
    FROM 
        docentes_mapuche
    $whereClause
    ORDER BY 
        apellidonombre_desc, 
        nro_documento,
        categoria_desc,
        nro_cargo
    LIMIT :limit OFFSET :offset";

    try {
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        if (!empty($searchTerm)) {
            $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
        }
        
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Si no hay resultados, verificar si es por paginación o realmente vacío
        if (empty($resultados)) {
            $total = $this->contarDocentesMapuche($searchTerm);
            if ($total > 0 && $offset >= $total) {
                // El usuario está en una página más allá de los resultados
                $offset = 0;
                $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        return [
            'data' => $resultados,
            'total' => $this->contarDocentesMapuche($searchTerm)
        ];

    } catch (PDOException $e) {
        error_log("Error en obtenerDocentesMapuche: " . $e->getMessage());
        return [
            'data' => [],
            'total' => 0,
            'error' => $e->getMessage()
        ];
    }
}

public function contarDocentesMapuche($searchTerm = '') {
    $sql = "SELECT COUNT(*) FROM (
        SELECT DISTINCT ON (
            apellidonombre_desc, 
            nro_documento,
            categoria_desc,
            nro_cargo
        ) 1
        FROM docentes_mapuche";
    
    if (!empty($searchTerm)) {
        $sql .= " WHERE apellidonombre_desc ILIKE :searchTerm";
    }
    
    $sql .= ") AS subquery";
    
    try {
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($searchTerm)) {
            $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
        }
        
        $stmt->execute();
        return (int)$stmt->fetchColumn();
        
    } catch (PDOException $e) {
        error_log("Error en contarDocentesMapuche: " . $e->getMessage());
        return 0;
    }
}

    // ================================
    // 3. DOCENTES GUARANI 
    // ================================
    public function obtenerDocentesGuarani($page = 1, $perPage = 50, $searchTerm = '', $year = '') {
        $offset = ($page - 1) * $perPage;
        $whereClause = '';
        $params = [];

        if (!empty($searchTerm)) {
            $whereClause = " WHERE num_doc_guarani::TEXT ILIKE :searchTerm";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }

        $sql = "SELECT 
            COALESCE(responsabilidad_academica_guarani, 'Sin información') AS \"Resp. Acad.\",
            COALESCE(propuesta_formativa_guarani, 'Sin información') AS \"Propuesta\",
            COALESCE(comision_guarani, 'Sin información') AS \"Comisión\",
            COALESCE(anio_guarani::TEXT, 'Sin información') AS \"Año\",
            COALESCE(periodo_guarani, 'Sin información') AS \"Periodo\",
            COALESCE(actividad_guarani, 'Sin información') AS \"Actividad\",
            COALESCE(codigo_guarani, 'Sin información') AS \"Código\",
            COALESCE(cursados_guarani, 'Sin información') AS \"Est\",
            COALESCE(num_doc_guarani::TEXT, 'Sin información') AS \"Num. Doc.\"
        FROM 
            docentes_guarani
        $whereClause
        ORDER BY 
            propuesta_formativa_guarani
        LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        if (!empty($searchTerm)) {
            $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
        }
        
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $this->contarDocentesGuarani($searchTerm)
        ];
    }

    public function contarDocentesGuarani($searchTerm = '') {
        $sql = "SELECT COUNT(*) FROM docentes_guarani";
        
        if (!empty($searchTerm)) {
            $sql .= " WHERE num_doc_guarani::TEXT ILIKE :searchTerm";
        }
        
        $stmt = $this->conn->prepare($sql);
        
        if (!empty($searchTerm)) {
            $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
        }
        
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}


// Configuración de conexión
try {
    $dsn = "pgsql:host={$config_tinkuy['host']};port={$config_tinkuy['port']};dbname={$config_tinkuy['dbname']}";
    $conn = new PDO($dsn, $config_tinkuy['user'], $config_tinkuy['password']);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $consultas = new ConsultasDocentes($conn);

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
        $response = [];
        $export = isset($_GET['export']) && $_GET['export'] === 'true';
        $page = $export ? 1 : (isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1);
        $perPage = $export ? 100000 : 50;

        $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
        // Si viene 'all' o vacío, usamos null para que el filtro no se aplique
        $year = (isset($_GET['year']) && $_GET['year'] !== '' && $_GET['year'] !== 'all') ? (int)$_GET['year'] : null;
        $type = $_GET['type'] ?? '';

        switch ($_GET['action']) {
            case 'getData':
                try {
                    // Validación de parámetros requeridos
                    if (!isset($_GET['type'])) {
                        throw new Exception("Tipo de consulta no especificado");
                    }

                    // Sanitización de parámetros
                    $type = $_GET['type'];
                    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
                    $perPage = isset($_GET['perPage']) ? max(1, (int)$_GET['perPage']) : 50;
                    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
                    $year = (isset($_GET['year']) && $_GET['year'] !== '' && $_GET['year'] !== 'all') ? (int)$_GET['year'] : null;

                    // Validación de tipos permitidos
                    $allowedTypes = ['guarani', 'mapuche', 'combinados'];
                    if (!in_array($type, $allowedTypes)) {
                        throw new Exception("Tipo de consulta no válido. Opciones permitidas: " . implode(', ', $allowedTypes));
                    }

                    // Ejecución de consultas (pasando $year correctamente)
                    $result = match($type) {
                        'guarani'    => $consultas->obtenerDocentesGuarani($page, $perPage, $searchTerm, $year),
                        'mapuche'    => $consultas->obtenerDocentesMapuche($page, $perPage, $searchTerm, $year),
                        'combinados' => $consultas->docentesCombinados($page, $perPage, $searchTerm, $year)
                    };

                    // Validación de estructura de respuesta
                    if (!isset($result['data']) || !isset($result['total'])) {
                        throw new Exception("Estructura de respuesta inválida desde el modelo");
                    }

                    $response = [
                        'success' => true,
                        'data' => $result['data'],
                        'pagination' => [
                            'current_page' => $page,
                            'per_page' => $perPage,
                            'total' => $result['total'],
                            'total_pages' => ceil($result['total'] / $perPage)
                        ]
                    ];

                } catch (Exception $e) {
                    // Manejo centralizado de errores
                    header('Content-Type: application/json');
                    $response = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'request_params' => $_GET // Para debugging
                    ];
                    echo json_encode($response);
                    exit;
                }
                break;

            default:
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Acción no válida',
                    'actions_allowed' => ['getData']
                ]);
                exit;
        }

        echo json_encode($response);
        exit;
    }

    throw new Exception("Solicitud no válida");

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}

?>