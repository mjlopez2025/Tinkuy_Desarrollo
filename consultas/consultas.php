<?php
header('Content-Type: application/json');
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
    public function docentesCombinados($page = 1, $perPage = 50, $searchTerm = '') {
        $offset = ($page - 1) * $perPage;
        $whereClause = '';
        $params = [];

        if (!empty($searchTerm)) {
            $whereClause = " AND m.apellidonombre_desc ILIKE :searchTerm";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
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
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        if (!empty($searchTerm)) {
            $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
        }
        
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $this->contarDocentesCombinados($searchTerm)
        ];
    }

    public function contarDocentesCombinados($searchTerm = '') {
        $whereClause = '';
        $params = [];

        if (!empty($searchTerm)) {
            $whereClause = " AND m.apellidonombre_desc ILIKE :searchTerm";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }

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
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    // ================================
    // 2. DOCENTES MAPUCHE 
    // ================================
    public function obtenerDocentesMapuche($page = 1, $perPage = 50, $searchTerm = '') {
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
    public function obtenerDocentesGuarani($page = 1, $perPage = 50, $searchTerm = '') {
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
        
        switch ($_GET['action']) {
            case 'getData':
                if (!isset($_GET['type'])) {
                    throw new Exception("Tipo de consulta no especificado");
                }
                
                $result = match($_GET['type']) {
                    'guarani' => $consultas->obtenerDocentesGuarani($page, $perPage, $searchTerm),
                    'mapuche' => $consultas->obtenerDocentesMapuche($page, $perPage, $searchTerm),
                    'combinados' => $consultas->docentesCombinados($page, $perPage, $searchTerm),
                    default => throw new Exception("Tipo de consulta no válido")
                };
                
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
                break;
                
            default:
                throw new Exception("Acción no válida");
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