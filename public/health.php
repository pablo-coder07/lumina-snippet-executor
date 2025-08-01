<?php
/**
 * Health Check Mejorado - Contar archivos en directorio correcto
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
$code_snippets_dir = __DIR__ . '/code_snippets/';  // DIRECTORIO ALTERNATIVO

// DIAGNÓSTICO COMPLETO DEL FILESYSTEM
$debug_info = [
    'paths' => [
        'current_dir' => __DIR__,
        'snippets_dir' => $snippets_dir,
        'code_snippets_dir' => $code_snippets_dir,  // AGREGAR DIRECTORIO ALTERNATIVO
        'realpath_current' => realpath(__DIR__),
        'realpath_snippets' => realpath($snippets_dir),
        'realpath_code_snippets' => realpath($code_snippets_dir)  // AGREGAR
    ],
    'directory_listing' => [],
    'permissions' => [
        'current_dir_readable' => is_readable(__DIR__),
        'current_dir_writable' => is_writable(__DIR__),
        'current_dir_permissions' => substr(sprintf('%o', fileperms(__DIR__)), -4)
    ],
    'creation_attempts' => [],
    'environment' => [
        'user' => get_current_user(),
        'uid' => getmyuid(),
        'gid' => getmygid(),
        'umask' => sprintf('%04o', umask())
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

// VERIFICAR SI EXISTE ALGO LLAMADO 'snippets'
$snippets_exists_as_file = is_file($snippets_dir);
$snippets_exists_as_dir = is_dir($snippets_dir);
$code_snippets_exists_as_dir = is_dir($code_snippets_dir);

$debug_info['snippets_analysis'] = [
    'exists_as_file' => $snippets_exists_as_file,
    'exists_as_dir' => $snippets_exists_as_dir,
    'exists_somehow' => file_exists($snippets_dir),
    'is_link' => is_link($snippets_dir),
    'stat_info' => @stat($snippets_dir),
    'code_snippets_exists' => $code_snippets_exists_as_dir  // AGREGAR
];

// INTENTAR MÚLTIPLES MÉTODOS DE CREACIÓN
$creation_methods = [];

// Método 1: mkdir normal para snippets
if (!is_dir($snippets_dir)) {
    $method1_result = @mkdir($snippets_dir, 0777, true);
    $creation_methods['mkdir_normal'] = [
        'attempted' => true,
        'success' => $method1_result,
        'exists_after' => is_dir($snippets_dir)
    ];
}

// Método 2: Eliminar y recrear si existe como archivo
if (is_file($snippets_dir)) {
    $unlink_result = @unlink($snippets_dir);
    $method2_mkdir = @mkdir($snippets_dir, 0777, true);
    $creation_methods['unlink_and_mkdir'] = [
        'unlink_success' => $unlink_result,
        'mkdir_success' => $method2_mkdir,
        'exists_after' => is_dir($snippets_dir)
    ];
}

// Método 3: Asegurar que code_snippets existe
if (!is_dir($code_snippets_dir)) {
    $method3_result = @mkdir($code_snippets_dir, 0777, true);
    $creation_methods['ensure_code_snippets'] = [
        'attempted' => true,
        'directory' => $code_snippets_dir,
        'success' => $method3_result,
        'exists_after' => is_dir($code_snippets_dir)
    ];
}

$debug_info['creation_attempts'] = $creation_methods;

// INTENTAR ESCRIBIR ARCHIVO DE PRUEBA
$test_results = [];

$test_dirs = [
    'snippets' => $snippets_dir,
    'code_snippets' => $code_snippets_dir,
    'current' => __DIR__ . '/'
];

foreach ($test_dirs as $test_name => $test_dir) {
    if ($test_dir && is_dir($test_dir)) {
        $test_file = $test_dir . 'test_' . time() . '.txt';
        $write_result = @file_put_contents($test_file, 'test content');
        
        $test_results[$test_name] = [
            'directory' => $test_dir,
            'test_file' => $test_file,
            'write_success' => $write_result !== false,
            'bytes_written' => $write_result ?: 0,
            'file_exists_after' => file_exists($test_file)
        ];
        
        // Limpiar archivo de prueba
        if (file_exists($test_file)) {
            @unlink($test_file);
        }
    }
}

$debug_info['write_tests'] = $test_results;

// CONTAR ARCHIVOS PHP EN AMBOS DIRECTORIOS
$php_files_count = 0;
$php_files_list = [];

// Buscar en directorio snippets principal
if (is_dir($snippets_dir)) {
    $files = @glob($snippets_dir . '*.php');
    if ($files) {
        $php_files_count += count($files);
        foreach ($files as $file) {
            $php_files_list[] = [
                'name' => basename($file),
                'directory' => 'snippets',
                'size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
    }
}

// BUSCAR EN DIRECTORIO code_snippets (AQUÍ ESTÁN LOS ARCHIVOS)
if (is_dir($code_snippets_dir)) {
    $files = @glob($code_snippets_dir . '*.php');
    if ($files) {
        $php_files_count += count($files);
        foreach ($files as $file) {
            $php_files_list[] = [
                'name' => basename($file),
                'directory' => 'code_snippets',  // IDENTIFICAR EL DIRECTORIO
                'size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
    }
}

// USAR EL DIRECTORIO QUE FUNCIONE
$working_directory = null;
$working_dir_type = null;

if (is_dir($snippets_dir) && is_writable($snippets_dir)) {
    $working_directory = $snippets_dir;
    $working_dir_type = 'snippets';
} elseif (is_dir($code_snippets_dir) && is_writable($code_snippets_dir)) {
    $working_directory = $code_snippets_dir;
    $working_dir_type = 'code_snippets';  // IDENTIFICAR COMO ALTERNATIVO
} elseif (is_writable(__DIR__)) {
    $working_directory = __DIR__ . '/';
    $working_dir_type = 'current';
}

// INFORMACIÓN FINAL DEL SISTEMA
$health = [
    'status' => $php_files_count > 0 ? 'healthy' : 'degraded',  // CAMBIAR LÓGICA
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
        'snippets_dir' => is_dir($snippets_dir) ? 'exists' : 'missing',
        'snippets_writable' => is_writable($snippets_dir) ? 'yes' : 'no',
        'code_snippets_dir' => is_dir($code_snippets_dir) ? 'exists' : 'missing',  // AGREGAR
        'code_snippets_writable' => is_writable($code_snippets_dir) ? 'yes' : 'no',  // AGREGAR
        'snippets_count' => $php_files_count,  // CONTAR TOTAL
        'snippets_by_directory' => [  // DESGLOSE POR DIRECTORIO
            'snippets' => count(array_filter($php_files_list, function($f) { return $f['directory'] === 'snippets'; })),
            'code_snippets' => count(array_filter($php_files_list, function($f) { return $f['directory'] === 'code_snippets'; }))
        ],
        'disk_free_space' => is_dir($working_directory ?: __DIR__) ? disk_free_space($working_directory ?: __DIR__) : 0,
        'working_directory' => $working_directory,
        'working_dir_type' => $working_dir_type,
        'recent_files' => array_slice($php_files_list, -5)  // ÚLTIMOS 5 DE AMBOS DIRECTORIOS
    ],
    'debug_info' => $debug_info
];

// LOGGING EXTENSIVO
error_log("=== HEALTH CHECK DEBUG ===");
error_log("Snippets dir exists: " . (is_dir($snippets_dir) ? 'YES' : 'NO'));
error_log("Code snippets dir exists: " . (is_dir($code_snippets_dir) ? 'YES' : 'NO'));
error_log("Total PHP files: " . $php_files_count);
error_log("Working directory: " . ($working_directory ?: 'NONE'));
error_log("PHP files list: " . json_encode($php_files_list));

// SIEMPRE devolver 200
http_response_code(200);

echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
