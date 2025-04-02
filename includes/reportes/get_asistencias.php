<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Habilitar reporte de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    $grupo_id = $_POST['grupo'] ?? '';
    $profesor_id = $_POST['profesor'] ?? '';
    $fechas = $_POST['fechas'] ?? '';

    // Validar que se haya seleccionado un grupo
    if (empty($grupo_id)) {
        throw new Exception('Debe seleccionar un grupo');
    }

    $where_conditions = ["e.grupo_id = ?"]; // Condición base
    $params = [$grupo_id];

    if (!empty($profesor_id)) {
        $where_conditions[] = "a.profesor_id = ?";
        $params[] = $profesor_id;
    }

    if (!empty($fechas)) {
        $fechas_array = explode(' - ', $fechas);
        if (count($fechas_array) == 2) {
            $where_conditions[] = "DATE(a.fecha_hora) BETWEEN ? AND ?";
            $params[] = $fechas_array[0];
            $params[] = $fechas_array[1];
        }
    }

    // Primero obtenemos todos los estudiantes del grupo
    $sql_estudiantes = "
        SELECT e.id, e.nombre, e.apellidos, e.documento
        FROM estudiantes e
        WHERE e.grupo_id = ? AND e.activo = 1
        ORDER BY e.apellidos, e.nombre";

    $stmt_estudiantes = $conn->prepare($sql_estudiantes);
    $stmt_estudiantes->execute([$grupo_id]);
    $estudiantes = $stmt_estudiantes->fetchAll(PDO::FETCH_ASSOC);

    if (empty($estudiantes)) {
        throw new Exception('No hay estudiantes registrados en este grupo');
    }

    // Obtenemos las fechas con registros dentro del rango seleccionado
    $sql_fechas = "
        SELECT DISTINCT DATE(fecha_hora) as fecha
        FROM asistencias a
        JOIN estudiantes e ON a.estudiante_id = e.id
        WHERE e.grupo_id = ?";
    
    $params_fechas = [$grupo_id];

    if (!empty($fechas)) {
        $fechas_array = explode(' - ', $fechas);
        if (count($fechas_array) == 2) {
            $sql_fechas .= " AND DATE(fecha_hora) BETWEEN ? AND ?";
            $params_fechas[] = $fechas_array[0];
            $params_fechas[] = $fechas_array[1];
        }
    }

    if (!empty($profesor_id)) {
        $sql_fechas .= " AND a.profesor_id = ?";
        $params_fechas[] = $profesor_id;
    }

    $sql_fechas .= " ORDER BY fecha";

    $stmt_fechas = $conn->prepare($sql_fechas);
    $stmt_fechas->execute($params_fechas);
    $fechas = $stmt_fechas->fetchAll(PDO::FETCH_COLUMN);

    // Si no hay fechas con registros, mostrar mensaje
    if (empty($fechas)) {
        echo json_encode([
            'error' => true,
            'message' => 'No hay registros de asistencia para el período seleccionado'
        ]);
        exit;
    }

    // Obtenemos todas las asistencias
    $sql_asistencias = "
        SELECT 
            e.id as estudiante_id,
            e.nombre,
            e.apellidos,
            e.documento,
            DATE(a.fecha_hora) as fecha
        FROM estudiantes e
        LEFT JOIN asistencias a ON e.id = a.estudiante_id 
            AND DATE(a.fecha_hora) IN (" . str_repeat('?,', count($fechas)-1) . "?)
        WHERE e.grupo_id = ? AND e.activo = 1";

    $params = array_merge($fechas, [$grupo_id]);
    
    if (!empty($profesor_id)) {
        $sql_asistencias .= " AND (a.profesor_id = ? OR a.profesor_id IS NULL)";
        $params[] = $profesor_id;
    }
    
    $sql_asistencias .= " ORDER BY e.apellidos, e.nombre, fecha";

    $stmt_asistencias = $conn->prepare($sql_asistencias);
    $stmt_asistencias->execute($params);
    $asistencias = $stmt_asistencias->fetchAll(PDO::FETCH_ASSOC);

    // Organizamos los datos
    $data = [];
    $estudiantes_procesados = [];
    
    foreach ($estudiantes as $estudiante) {
        $row = [
            'estudiante' => $estudiante['apellidos'] . ', ' . $estudiante['nombre'],
            'documento' => $estudiante['documento']
        ];
        
        // Inicializar todas las fechas con una X simple
        foreach ($fechas as $fecha) {
            $row[$fecha] = 'X';
        }
        
        $data[] = $row;
        $estudiantes_procesados[$estudiante['id']] = &$data[count($data) - 1];
    }
    
    // Marcar las asistencias con un check
    foreach ($asistencias as $asistencia) {
        if (isset($estudiantes_procesados[$asistencia['estudiante_id']]) && $asistencia['fecha'] && !is_null($asistencia['fecha'])) {
            $estudiantes_procesados[$asistencia['estudiante_id']][$asistencia['fecha']] = '✓';
        }
    }

    // Preparamos las columnas
    $columns = [
        ['title' => 'Estudiante', 'data' => 'estudiante'],
        ['title' => 'Documento', 'data' => 'documento']
    ];

    foreach ($fechas as $fecha) {
        $fecha_formateada = date('d/m/Y', strtotime($fecha));
        $columns[] = [
            'title' => $fecha_formateada,
            'data' => $fecha
        ];
    }

    echo json_encode([
        'data' => array_values($data),
        'columns' => $columns
    ]);

} catch (Exception $e) {
    error_log("Error en get_asistencias.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
} 