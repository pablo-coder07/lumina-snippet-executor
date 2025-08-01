<?php
/**
 * Health Check con Debug Completo - Identificar problema del directorio
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

// DIAGNÓSTICO COMPLETO DEL FILESYSTEM
$debug_info = [
    'paths' => [
        'current_dir' => __DIR__,
        'snippets_dir' => $snippets_dir,
        'realpath_current' => realpath(__DIR__),
        'realpath_snippets' => realpath($snippets_dir)
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

$debug_info['snippets_analysis'] = [
    'exists_as_file' => $snippets_exists_as_file,
    'exists_as_dir' => $snippets_exists_as_dir,
    'exists_somehow' => file_exists($snippets_dir),
    'is_link' => is_link($snippets_dir),
    'stat_info' => @stat($snippets_dir)
];

// INTENTAR MÚLTIPLES MÉTODOS DE CREACIÓN
$creation_methods = [];

// Método 1: mkdir normal
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

// Método 3: Usar directorio alternativo si el principal falla
$alt_snippets_dir = __DIR__ . '/code_snippets/';
if (!is_dir($snippets_dir) && !is_dir($alt_snippets_dir)) {
    $method3_result = @mkdir($alt_snippets_dir, 0777, true);
    $creation_methods['alternative_directory'] = [
        'attempted' => true,
        'directory' => $alt_snippets_dir,
        'success' => $method3_result,
        'exists_after' => is_dir($alt_snippets_dir)
    ];
}

$debug_info['creation_attempts'] = $creation_methods;

// INTENTAR ESCRIBIR ARCHIVO DE PRUEBA
$test_results = [];

$test_dirs = [
    'snippets' => $snippets_dir,
    'alternative' => $alt_snippets_dir ?? null,
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

// USAR EL DIRECTORIO QUE FUNCIONE
$working_directory = null;
$working_dir_type = null;

if (is_dir($snippets_dir) && is_writable($snippets_dir)) {
    $working_directory = $snippets_dir;
    $working_dir_type = 'snippets';
} elseif (isset($alt_snippets_dir) && is_dir($alt_snippets_dir) && is_writable($alt_snippets_dir)) {
    $working_directory = $alt_snippets_dir;
    $working_dir_type = 'alternative';
} elseif (is_writable(__DIR__)) {
    $working_directory = __DIR__ . '/';
    $working_dir_type = 'current';
}

// INFORMACIÓN FINAL DEL SISTEMA
$health = [
    'status' => is_dir($snippets_dir) && is_writable($snippets_dir) ? 'healthy' : 'degraded',
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
        'snippets_count' => 0,
        'disk_free_space' => is_dir($snippets_dir) ? disk_free_space($snippets_dir) : 0,
        'working_directory' => $working_directory,
        'working_dir_type' => $working_dir_type
    ],
    'debug_info' => $debug_info
];

// Contar archivos PHP si existe el directorio
if (is_dir($snippets_dir)) {
    $php_files = glob($snippets_dir . '*.php');
    $health['storage_info']['snippets_count'] = count($php_files);
    $health['storage_info']['recent_files'] = array_slice($php_files, -3);
}

// LOGGING EXTENSIVO
error_log("=== HEALTH CHECK DEBUG ===");
error_log("Snippets dir exists: " . (is_dir($snippets_dir) ? 'YES' : 'NO'));
error_log("Current dir writable: " . (is_writable(__DIR__) ? 'YES' : 'NO'));
error_log("Working directory: " . ($working_directory ?: 'NONE'));
error_log("Creation attempts: " . json_encode($creation_methods));

// SIEMPRE devolver 200
http_response_code(200);

echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
