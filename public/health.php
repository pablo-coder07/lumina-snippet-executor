<?php
/**
 * Health Check ACTUALIZADO - Para estructura organizada por usuario
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://lumina.market');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$snippets_dir = __DIR__ . '/snippets/';

// DIAGNÓSTICO DE ESTRUCTURA ORGANIZADA
$debug_info = [
    'paths' => [
        'current_dir' => __DIR__,
        'snippets_dir' => $snippets_dir,
        'realpath_current' => realpath(__DIR__),
        'realpath_snippets' => realpath($snippets_dir)
    ],
    'directory_listing' => [],
    'user_directories' => [],
    'permissions' => [
        'current_dir_readable' => is_readable(__DIR__),
        'current_dir_writable' => is_writable(__DIR__)
    ]
];

// LISTAR CONTENIDO DEL DIRECTORIO ACTUAL
$current_files = @scandir(__DIR__);
if ($current_files) {
    foreach ($current_files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filepath = __DIR__ . '/' . $file;
            $debug_info['directory_listing'][] = [
                'name' => $file,
                'type' => is_dir($filepath) ? 'directory' : 'file',
                'permissions' => substr(sprintf('%o', fileperms($filepath)), -4),
                'size' => is_file($filepath) ? filesize($filepath) : 0
            ];
        }
    }
}

// VERIFICAR DIRECTORIO SNIPPETS
$snippets_exists_as_dir = is_dir($snippets_dir);
$snippets_writable = $snippets_exists_as_dir && is_writable($snippets_dir);

// CONTAR ARCHIVOS EN ESTRUCTURA ORGANIZADA
$total_php_files = 0;
$php_files_list = [];
$user_directories = [];

if ($snippets_exists_as_dir) {
    // 1. Buscar carpetas de usuario
    $user_dirs = glob($snippets_dir . 'usuario_*', GLOB_ONLYDIR);
    
    foreach ($user_dirs as $user_dir) {
        $user_name = basename($user_dir);
        $user_files = glob($user_dir . '/*.php');
        $user_file_count = count($user_files);
        $total_php_files += $user_file_count;
        
        // Información de la carpeta de usuario
        $user_info = [
            'name' => $user_name,
            'path' => $user_dir,
            'file_count' => $user_file_count,
            'writable' => is_writable($user_dir),
            'files' => []
        ];
        
        // Detalles de archivos del usuario
        foreach ($user_files as $file) {
            $file_info = [
                'name' => basename($file),
                'full_path' => $file,
                'size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file)),
                'user_directory' => $user_name
            ];
            
            $php_files_list[] = $file_info;
            $user_info['files'][] = $file_info;
        }
        
        $user_directories[] = $user_info;
    }
    
    // 2. También buscar archivos sueltos (no migrados)
    $loose_files = glob($snippets_dir . '*.php');
    if (!empty($loose_files)) {
        foreach ($loose_files as $file) {
            $php_files_list[] = [
                'name' => basename($file),
                'full_path' => $file,
                'size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file)),
                'user_directory' => 'root_directory' // Archivos sin migrar
            ];
            $total_php_files++;
        }
    }
}

$debug_info['user_directories'] = $user_directories;

// TEST DE ESCRITURA
$write_test_success = false;
if ($snippets_writable) {
    $test_file = $snippets_dir . 'test_' . time() . '.txt';
    $write_result = @file_put_contents($test_file, 'test content');
    $write_test_success = $write_result !== false;
    
    if (file_exists($test_file)) {
        @unlink($test_file);
    }
}

// INFORMACIÓN FINAL DEL SISTEMA CON ESTRUCTURA ORGANIZADA
$health = [
    'status' => ($snippets_exists_as_dir && $snippets_writable) ? 'healthy' : 'degraded',
    'timestamp' => time(),
    'date' => date('Y-m-d H:i:s'),
    'environment' => $_ENV['ENVIRONMENT'] ?? 'production',
    'server_info' => [
        'php_version' => PHP_VERSION,
        'memory_usage' => memory_get_usage(true),
        'memory_limit' => ini_get('memory_limit'),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ],
    'storage_info' => [
        'snippets_dir' => $snippets_exists_as_dir ? 'exists' : 'missing',
        'snippets_writable' => $snippets_writable ? 'yes' : 'no',
        'organization_type' => 'user_directories',
        'user_directories_count' => count($user_directories),
        'snippets_count' => $total_php_files,
        'snippets_by_location' => [
            'organized_in_user_dirs' => $total_php_files - count(glob($snippets_dir . '*.php')),
            'loose_in_root' => count(glob($snippets_dir . '*.php'))
        ],
        'user_breakdown' => array_map(function($user) {
            return [
                'name' => $user['name'],
                'files' => $user['file_count'],
                'writable' => $user['writable']
            ];
        }, $user_directories),
        'write_test' => $write_test_success ? 'success' : 'failed',
        'disk_free_space' => is_dir($snippets_dir) ? disk_free_space($snippets_dir) : 0,
        'working_directory' => $snippets_writable ? $snippets_dir : null,
        'recent_files' => array_slice($php_files_list, -5) // Últimos 5 de todos los usuarios
    ],
    'debug_info' => $debug_info
];

// LOG DETALLADO
error_log("=== HEALTH CHECK (ORGANIZED) ===");
error_log("Snippets dir exists: " . ($snippets_exists_as_dir ? 'YES' : 'NO'));
error_log("Snippets writable: " . ($snippets_writable ? 'YES' : 'NO'));
error_log("User directories: " . count($user_directories));
error_log("Total PHP files: " . $total_php_files);
error_log("Organization: user_directories");

foreach ($user_directories as $user_dir) {
    error_log("User " . $user_dir['name'] . ": " . $user_dir['file_count'] . " files");
}

http_response_code(200);
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
