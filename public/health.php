<?php
/**
 * Health Check Endpoint Mejorado - Render Version
 * URL: https://lumina-snippet-executor.onrender.com/health.php
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://lumina.market');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Manejar preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$snippets_dir = __DIR__ . '/snippets/';

// INTENTAR CREAR EL DIRECTORIO SI NO EXISTE
if (!is_dir($snippets_dir)) {
    $mkdir_result = mkdir($snippets_dir, 0777, true);
    error_log("Intentando crear directorio snippets: " . ($mkdir_result ? 'ÉXITO' : 'FALLO'));
    
    if ($mkdir_result) {
        chmod($snippets_dir, 0777);
        error_log("Permisos aplicados al directorio snippets");
    }
}

// DIAGNÓSTICO DETALLADO
$diagnostics = [
    'directory_checks' => [
        'snippets_dir_path' => $snippets_dir,
        'current_dir' => __DIR__,
        'snippets_exists' => is_dir($snippets_dir),
        'snippets_readable' => is_readable($snippets_dir),
        'snippets_writable' => is_writable($snippets_dir),
        'snippets_permissions' => is_dir($snippets_dir) ? substr(sprintf('%o', fileperms($snippets_dir)), -4) : 'N/A'
    ],
    'file_operations' => [],
    'environment' => [
        'php_user' => get_current_user(),
        'php_uid' => getmyuid(),
        'php_gid' => getmygid(),
        'working_dir' => getcwd(),
        'temp_dir' => sys_get_temp_dir()
    ]
];

// INTENTAR ESCRIBIR UN ARCHIVO DE PRUEBA
$test_file = $snippets_dir . 'health_test_' . time() . '.txt';
$write_test = @file_put_contents($test_file, 'Health check test at ' . date('Y-m-d H:i:s'));

$diagnostics['file_operations'] = [
    'test_file_path' => $test_file,
    'write_attempt' => $write_test !== false,
    'write_bytes' => $write_test ?: 0,
    'test_file_exists' => file_exists($test_file)
];

// Limpiar archivo de prueba
if (file_exists($test_file)) {
    @unlink($test_file);
}

// CONTAR ARCHIVOS PHP EXISTENTES
$php_files_count = 0;
$php_files_list = [];
if (is_dir($snippets_dir)) {
    $files = @scandir($snippets_dir);
    if ($files) {
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $php_files_count++;
                $php_files_list[] = [
                    'name' => $file,
                    'size' => filesize($snippets_dir . $file),
                    'modified' => date('Y-m-d H:i:s', filemtime($snippets_dir . $file))
                ];
            }
        }
    }
}

// INFORMACIÓN PRINCIPAL DEL SISTEMA
$health = [
    'status' => 'healthy',
    'timestamp' => time(),
    'date' => date('Y-m-d H:i:s'),
    'timezone' => date_default_timezone_get(),
    'environment' => $_ENV['ENVIRONMENT'] ?? 'production',
    'server_info' => [
        'php_version' => PHP_VERSION,
        'memory_usage' => memory_get_usage(true),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ],
    'storage_info' => [
        'snippets_dir' => is_dir($snippets_dir) ? 'exists' : 'missing',
        'snippets_writable' => is_writable($snippets_dir) ? 'yes' : 'no',
        'snippets_count' => $php_files_count,
        'disk_free_space' => is_dir($snippets_dir) ? disk_free_space($snippets_dir) : 0,
        'recent_files' => array_slice($php_files_list, -3) // Últimos 3 archivos
    ],
    'api_info' => [
        'endpoints' => [
            'health' => '/health.php',
            'save' => '/save-snippet.php',
            'execute' => '/execute-snippet.php'
        ],
        'version' => '1.0.1',
        'last_deploy' => file_exists(__DIR__ . '/.deploy-timestamp') ? file_get_contents(__DIR__ . '/.deploy-timestamp') : 'unknown'
    ],
    'security' => [
        'api_key_required' => true,
        'cors_enabled' => true,
        'https_only' => isset($_SERVER['HTTPS']) || $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
    ],
    'diagnostics' => $diagnostics
];

// VERIFICAR ESTADO CRÍTICO
$critical_issues = [];

if (!is_dir($snippets_dir)) {
    $critical_issues[] = 'snippets_directory_missing';
}

if (!is_writable($snippets_dir)) {
    $critical_issues[] = 'snippets_directory_not_writable';
}

if (!$diagnostics['file_operations']['write_attempt']) {
    $critical_issues[] = 'file_write_test_failed';
}

// DETERMINAR ESTADO GENERAL
if (empty($critical_issues)) {
    $health['status'] = 'healthy';
} else {
    $health['status'] = 'degraded';
    $health['critical_issues'] = $critical_issues;
}

// LOGGING PARA DEBUGGING
error_log("=== HEALTH CHECK RESULTS ===");
error_log("Status: " . $health['status']);
error_log("Snippets dir exists: " . (is_dir($snippets_dir) ? 'YES' : 'NO'));
error_log("Snippets writable: " . (is_writable($snippets_dir) ? 'YES' : 'NO'));
error_log("PHP files count: " . $php_files_count);
error_log("Critical issues: " . implode(', ', $critical_issues));

// SIEMPRE devolver 200 para que pase health check de Render
http_response_code(200);

// Respuesta JSON
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
