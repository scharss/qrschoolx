<?php
session_start();
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use chillerlan\QRCode\{QRCode, QROptions};
use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

// Verificar que el usuario está autenticado y es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrador') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar si se ha subido algún archivo
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se ha subido ningún archivo o ha ocurrido un error en la subida']);
    exit;
}

// Obtener información del archivo
$tempFile = $_FILES['excel_file']['tmp_name'];
$originalName = $_FILES['excel_file']['name'];
$fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

// Variables para almacenar los datos
$rows = [];

try {
    if ($fileExt === 'csv') {
        // Procesar archivo CSV
        $reader = IOFactory::createReader('Csv');
        $spreadsheet = $reader->load($tempFile);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Eliminar la primera fila (encabezados)
        array_shift($rows);
    } 
    else if ($fileExt === 'xlsx' || $fileExt === 'xls') {
        // Procesar archivos Excel (tanto XLSX como XLS)
        $reader = IOFactory::createReaderForFile($tempFile);
        $spreadsheet = $reader->load($tempFile);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        
        // Eliminar la primera fila (encabezados)
        array_shift($rows);
    }
    else {
        throw new Exception('Formato de archivo no compatible. Use archivos .xlsx, .xls o .csv');
    }
    
    // Verificar si hay datos
    if (count($rows) == 0) {
        throw new Exception('El archivo no contiene datos');
    }
    
    // Verificar que haya suficientes columnas
    $primeraFila = $rows[0] ?? [];
    if (count($primeraFila) < 3) {
        throw new Exception('El formato del archivo no es válido. Asegúrese de incluir columnas para Nombre, Apellidos y Documento.');
    }
    
    // Conectar a la base de datos
    $db = new Database();
    $conn = $db->connect();
    
    // Variables para el seguimiento de resultados
    $estudiantes_creados = 0;
    $estudiantes_no_creados = 0;
    $mensajes_error = [];
    
    // Configurar opciones del QR que se usarán para todos los estudiantes
    $options = new QROptions([
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel' => QRCode::ECC_L,
        'scale' => 30,
        'imageBase64' => true,
        'bgColor' => [255, 255, 255],
        'fpColor' => [0, 0, 0],
        'quietzoneSize' => 1
    ]);
    
    $qrcode = new QRCode($options);
    
    // Obtener los grupos para mapear nombres a IDs
    $grupos = [];
    $stmt_grupos = $conn->query("SELECT id, nombre FROM grupos WHERE activo = 1");
    while ($grupo = $stmt_grupos->fetch(PDO::FETCH_ASSOC)) {
        $grupos[strtolower($grupo['nombre'])] = $grupo['id'];
    }
    
    // Procesar cada fila
    foreach ($rows as $index => $row) {
        // Ignorar filas vacías
        if (empty($row[0]) && empty($row[1]) && empty($row[2])) {
            continue;
        }
        
        // Obtener datos
        $nombre = htmlspecialchars(trim($row[0] ?? ''), ENT_QUOTES, 'UTF-8');
        $apellidos = htmlspecialchars(trim($row[1] ?? ''), ENT_QUOTES, 'UTF-8');
        $documento = htmlspecialchars(trim($row[2] ?? ''), ENT_QUOTES, 'UTF-8');
        
        // Verificar grupo si existe la columna
        $grupo_id = null;
        if (isset($row[3]) && !empty($row[3])) {
            $nombre_grupo = strtolower(trim($row[3]));
            if (isset($grupos[$nombre_grupo])) {
                $grupo_id = $grupos[$nombre_grupo];
            }
        }
        
        // Validar datos requeridos
        if (empty($nombre) || empty($apellidos) || empty($documento)) {
            $estudiantes_no_creados++;
            $mensajes_error[] = "Fila " . ($index + 2) . ": Faltan datos requeridos (nombre, apellidos o documento).";
            continue;
        }
        
        try {
            // Iniciar transacción por cada estudiante
            $conn->beginTransaction();
            
            // Verificar si ya existe un estudiante con el mismo documento
            $stmt = $conn->prepare("SELECT id FROM estudiantes WHERE documento = ?");
            $stmt->execute([$documento]);
            if ($stmt->fetch()) {
                $estudiantes_no_creados++;
                $mensajes_error[] = "Fila " . ($index + 2) . ": Ya existe un estudiante con el documento " . $documento;
                $conn->rollBack();
                continue;
            }
            
            // Insertar el estudiante
            $stmt = $conn->prepare("INSERT INTO estudiantes (nombre, apellidos, documento, grupo_id, qr_code, activo) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$nombre, $apellidos, $documento, $grupo_id, '']);
            
            // Obtener el ID del estudiante recién creado
            $estudiante_id = $conn->lastInsertId();
            
            // Generar el código QR con el ID del estudiante
            $qrImage = $qrcode->render((string)$estudiante_id);
            
            // Actualizar el estudiante con su código QR
            $stmt = $conn->prepare("UPDATE estudiantes SET qr_code = ? WHERE id = ?");
            $stmt->execute([$qrImage, $estudiante_id]);
            
            // Confirmar transacción
            $conn->commit();
            
            $estudiantes_creados++;
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $estudiantes_no_creados++;
            $mensajes_error[] = "Fila " . ($index + 2) . ": Error al crear estudiante: " . $e->getMessage();
            error_log("Error al crear estudiante: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
        }
    }
    
    // Preparar respuesta
    echo json_encode([
        'success' => true,
        'message' => "Importación completada. Estudiantes creados: $estudiantes_creados. Estudiantes no creados: $estudiantes_no_creados.",
        'estudiantes_creados' => $estudiantes_creados,
        'estudiantes_no_creados' => $estudiantes_no_creados,
        'errores' => $mensajes_error
    ]);
    
} catch (Exception $e) {
    error_log("Error en importar_excel.php: " . $e->getMessage() . " - Trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar el archivo: ' . $e->getMessage(),
        'error_details' => $e->getTraceAsString()
    ]);
} 