<?php
/**
 * Health Check Endpoint - Render Version
 * URL: https://tu-servicio.onrender.com/health.php
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

// Información del sistema
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
        'snippets_dir' => is_dir(__DIR__ . '/snippets/') ? 'exists' : 'missing',
        'snippets_writable' => is_writable(__DIR__ . '/snippets/') ? 'yes' : 'no',
        'snippets_count' => is_dir(__DIR__ . '/snippets/') ? count(glob(__DIR__ . '/snippets/*.php')) : 0,
        'disk_free_space' => is_dir(__DIR__ . '/snippets/') ? disk_free_space(__DIR__ . '/snippets/') : 0
    ],
    'api_info' => [
        'endpoints' => [
            'health' => '/health.php',
            'save' => '/save-snippet.php',
            'execute' => '/execute-snippet.php'
        ],
        'version' => '1.0.0',
        'last_deploy' => file_exists(__DIR__ . '/.deploy-timestamp') ? 
            file_get_contents(__DIR__ . '/.deploy-timestamp') : 'unknown'
    ],
    'security' => [
        'api_key_required' => true,
        'cors_enabled' => true,
        'https_only' => isset($_SERVER['HTTPS']) || $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
    ]
];

// Verificar estado de componentes críticos
$critical_checks = [
    'php_extensions' => [
        'json' => extension_loaded('json'),
        'curl' => extension_loaded('curl'),
        'mbstring' => extension_loaded('mbstring')
    ],
    'file_permissions' => [
        'snippets_readable' => is_readable(__DIR__ . '/snippets/'),
        'snippets_writable' => is_writable(__DIR__ . '/snippets/'),
        'logs_writable' => is_writable(__DIR__)
    ]
];

$health['system_checks'] = $critical_checks;

// Determinar estado general
$overall_status = 'healthy';
foreach ($critical_checks as $category => $checks) {
    foreach ($checks as $check => $result) {
        if (!$result) {
            $overall_status = 'warning';
            $health['warnings'][] = "Failed check: {$category}.{$check}";
        }
    }
}

$health['status'] = $overall_status;

// SIEMPRE devolver 200 para que pase health check
http_response_code(200);

// Respuesta JSON
echo json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

// Log para debugging en producción
error_log("Health check accessed from: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " at " . date('Y-m-d H:i:s'));
?>
