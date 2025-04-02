<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Habilitar reporte de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'profesor') {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    // Obtener los grupos asignados al profesor
    // Modificamos la consulta para obtener todos los grupos activos donde el profesor ha registrado asistencias
    $sql = "
        SELECT DISTINCT g.id, g.nombre
        FROM grupos g
        JOIN estudiantes e ON g.id = e.grupo_id
        JOIN asistencias a ON e.id = a.estudiante_id
        WHERE a.profesor_id = ? AND g.activo = 1
        ORDER BY g.nombre";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Si el profesor no tiene grupos asignados, devuelve un arreglo vacío
    if (empty($grupos)) {
        // Intentamos buscar todos los grupos activos
        $sql_todos = "SELECT id, nombre FROM grupos WHERE activo = 1 ORDER BY nombre";
        $stmt_todos = $conn->prepare($sql_todos);
        $stmt_todos->execute();
        $grupos = $stmt_todos->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode($grupos);

} catch (Exception $e) {
    error_log("Error en get_grupos_profesor.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error al obtener los grupos: ' . $e->getMessage()
    ]);
} 