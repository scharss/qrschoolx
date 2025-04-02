<?php
require_once 'vendor/autoload.php';

// Verificar si la clase está disponible
if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
    echo '<div style="color: red; font-weight: bold;">ERROR: La clase PhpOffice\PhpSpreadsheet\Spreadsheet no está disponible.</div>';
    echo '<p>Verifica que has ejecutado <code>composer require phpoffice/phpspreadsheet</code> y que la autoload.php está correctamente incluida.</p>';
    
    // Mostrar información adicional
    echo '<h3>Información de PHP:</h3>';
    echo '<pre>';
    echo 'PHP Version: ' . phpversion() . "\n";
    echo 'Loaded Extensions: ' . implode(', ', get_loaded_extensions()) . "\n";
    echo 'Include Path: ' . get_include_path() . "\n";
    echo '</pre>';
    
    // Verificar si vendor/autoload.php existe
    echo '<h3>Verificación de archivos:</h3>';
    $autoloadPath = __DIR__ . '/vendor/autoload.php';
    echo '<p>autoload.php ' . (file_exists($autoloadPath) ? 'existe' : 'NO existe') . '</p>';
    
    // Verificar si el directorio de PHPSpreadsheet existe
    $phpspreadsheetPath = __DIR__ . '/vendor/phpoffice/phpspreadsheet';
    echo '<p>Directorio PHPSpreadsheet ' . (is_dir($phpspreadsheetPath) ? 'existe' : 'NO existe') . '</p>';
    
} else {
    echo '<div style="color: green; font-weight: bold;">PHPSpreadsheet está correctamente instalado y disponible.</div>';
    
    // Mostrar la versión de PHPSpreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $reflection = new ReflectionClass($spreadsheet);
    echo '<p>Ubicación de la clase: ' . $reflection->getFileName() . '</p>';
    
    // Verificar si podemos crear un reader para xlsx
    try {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        echo '<p>Reader para archivos XLSX disponible.</p>';
    } catch (Exception $e) {
        echo '<p style="color: red;">Error al crear reader para XLSX: ' . $e->getMessage() . '</p>';
    }
    
    // Verificar si podemos crear un reader para csv
    try {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Csv');
        echo '<p>Reader para archivos CSV disponible.</p>';
    } catch (Exception $e) {
        echo '<p style="color: red;">Error al crear reader para CSV: ' . $e->getMessage() . '</p>';
    }
} 