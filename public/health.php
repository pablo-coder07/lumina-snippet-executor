<?php
// health.php MEJORADO - Con diagnóstico completo y debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
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

// Función de logging
function debug_log($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] HEALTH-CHECK: {$message}";
    if ($data !== null) {
        $log_entry .= " | Data: " . json_encode($data);
    }
    error_log($log_entry);
}

debug_log("=== HEALTH CHECK INICIADO ===");

$snippets_dir = __DIR__ . '/snippets/';
$current_time = time();

// Diagnóstico completo del sistema
$system_diagnostic = [
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'memory_limit' => ini_get('memory_limit'),
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'max_execution_time' => ini_get('max_execution_time'),
        'current_time' => date('Y-m-d H:i:s', $current_time),
        'timezone' => date_default_timezone_get()
    ],
    'directory_info' => [
        'current_dir' => __DIR__,
        'snippets_dir' => $snippets_dir,
        'current_dir_exists' => is_dir(__DIR__),
        'current_dir_readable' => is_readable(__DIR__),
        'current_dir_writable' => is_writable(__DIR__),
        'snippets_dir_exists' => is_dir($snippets_dir),
        'snippets_dir_readable' => is_readable($snippets_dir),
        'snippets_dir_writable' => is_writable($snippets_dir)
    ]
];

// Si el directorio snippets no existe, intentar crearlo
if (!is_dir($snippets_dir)) {
    debug_log("Snippets directory missing, attempting to create", ['path' => $snippets_dir]);
    
    $create_result = @mkdir($snippets_dir, 0755, true);
    $system_diagnostic['directory_info']['create_attempted'] = true;
    $system_diagnostic['directory_info']['create_success'] = $create_result;
    
    if ($create_result) {
        debug_log("Snippets directory created successfully");
    } else {
        debug_log("Failed to create snippets directory");
    }
}

// Actualizar estado después del intento de creación
$system_diagnostic['directory_info']['snippets_dir_exists'] = is_dir($snippets_dir);
$system_diagnostic['directory_info']['snippets_dir_writable'] = is_writable($snippets_dir);

// Análisis de archivos PHP
$php_files_analysis = [
    'total_count' => 0,
    'files' => [],
    'by_shortcode' => [],
    'recent_files' => [],
    'size_statistics' => [
        'total_size' => 0,
        'average_size' => 0,
        'largest_file' => null,
        'smallest_file' => null
    ]
];

if (is_dir($snippets_dir)) {
    $files = @scandir($snippets_dir);
    
    if ($files) {
        $php_files = [];
        $total_size = 0;
        $sizes = [];
        
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $file_path = $snippets_dir . $file;
                $file_size = filesize($file_path);
                $file_mtime = filemtime($file_path);
                
                $file_info = [
                    'name' => $file,
                    'size' => $file_size,
                    'modified' => $file_mtime,
                    'modified_readable' => date('Y-m-d H:i:s', $file_mtime),
                    'age_seconds' => $current_time - $file_mtime,
                    'readable' => is_readable($file_path)
                ];
                
                // Extraer información del shortcode del nombre del archivo
                if (preg_match('/^([a-zA-Z0-9_-]+?)_(?:v\d+_)?(\d+)\.php$/', $file, $matches)) {
                    $shortcode_name = $matches[1];
                    $timestamp = intval($matches[2]);
                    
                    $file_info['shortcode'] = $shortcode_name;
                    $file_info['timestamp'] = $timestamp;
                    
                    // Agrupar por shortcode
                    if (!isset($php_files_analysis['by_shortcode'][$shortcode_name])) {
                        $php_files_analysis['by_shortcode'][$shortcode_name] = [];
                    }
                    $php_files_analysis['by_shortcode'][$shortcode_name][] = $file_info;
                }
                
                $php_files[] = $file_info;
                $total_size += $file_size;
                $sizes[] = $file_size;
                
                debug_log("Analyzed file", [
                    'file' => $file,
                    'size' => $file_size,
                    'shortcode' => $file_info['shortcode'] ?? 'unknown'
                ]);
            }
        }
        
        // Ordenar archivos por fecha de modificación (más recientes primero)
        usort($php_files, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        $php_files_analysis['total_count'] = count($php_files);
        $php_files_analysis['files'] = $php_files;
        $php_files_analysis['recent_files'] = array_slice($php_files, 0, 5);
        
        // Estadísticas de tamaño
        if (!empty($sizes)) {
            $php_files_analysis['size_statistics']['total_size'] = $total_size;
            $php_files_analysis['size_statistics']['average_size'] = round($total_size / count($sizes));
            $php_files_analysis['size_statistics']['largest_file'] = [
                'size' => max($sizes),
                'file' => $php_files[array_search(max($sizes), array_column($php_files, 'size'))]['name']
            ];
            $php_files_analysis['size_statistics']['smallest_file'] = [
                'size' => min($sizes),
                'file' => $php_files[array_search(min($sizes), array_column($php_files, 'size'))]['name']
            ];
        }
        
        debug_log("File analysis completed", [
            'total_files' => count($php_files),
            'total_size' => $total_size,
            'unique_shortcodes' => count($php_files_analysis['by_shortcode'])
        ]);
    } else {
        debug_log("ERROR: Cannot read snippets directory");
        $php_files_analysis['error'] = 'Cannot read snippets directory';
    }
} else {
    debug_log("WARNING: Snippets directory does not exist");
    $php_files_analysis['error'] = 'Snippets directory does not exist';
}

// Test de escritura
$write_test = [
    'attempted' => false,
    'success' => false,
    'error' => null,
    'test_file' => null
];

if (is_dir($snippets_dir) && is_writable($snippets_dir)) {
    $test_filename = 'health_test_' . $current_time . '.txt';
    $test_filepath = $snippets_dir . $test_filename;
    $test_content = 'Health check write test - ' . date('Y-m-d H:i:s');
    
    $write_test['attempted'] = true;
    $write_test['test_file'] = $test_filename;
    
    $write_result = @file_put_contents($test_filepath, $test_content);
    
    if ($write_result !== false) {
        $write_test['success'] = true;
        // Limpiar archivo de prueba
        @unlink($test_filepath);
        debug_log("Write test: SUCCESS");
    } else {
        $write_test['error'] = 'Failed to write test file';
        debug_log("Write test: FAILED");
    }
} else {
    $write_test['error'] = 'Directory not writable or does not exist';
    debug_log("Write test: SKIPPED - Directory not available");
}

// Determinar estado general del sistema
$overall_status = 'healthy';
$issues = [];

if (!is_dir($snippets_dir)) {
    $overall_status = 'critical';
    $issues[] = 'Snippets directory does not exist';
} elseif (!is_writable($snippets_dir)) {
    $overall_status = 'degraded';
    $issues[] = 'Snippets directory is not writable';
} elseif (!$write_test['success']) {
    $overall_status = 'degraded';
    $issues[] = 'Write test failed';
}

if ($php_files_analysis['total_count'] === 0) {
    if ($overall_status === 'healthy') {
        $overall_status = 'warning';
    }
    $issues[] = 'No PHP snippets found';
}

// Información adicional del entorno
$environment_info = [
    'disk_space' => [
        'free_bytes' => is_dir($snippets_dir) ? disk_free_space($snippets_dir) : null,
        'total_bytes' => is_dir($snippets_dir) ? disk_total_space($snippets_dir) : null
    ],
    'php_extensions' => [
        'json' => extension_loaded('json'),
        'fileinfo' => extension_loaded('fileinfo'),
        'mbstring' => extension_loaded('mbstring')
    ],
    'request_info' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'request_time' => $_SERVER['REQUEST_TIME'] ?? $current_time
    ]
];

// Construir respuesta final
$health_response = [
    'status' => $overall_status,
    'timestamp' => $current_time,
    'date' => date('Y-m-d H:i:s', $current_time),
    'issues' => $issues,
    'system_diagnostic' => $system_diagnostic,
    'storage_info' => [
        'snippets_dir' => $snippets_dir,
        'snippets_dir_status' => is_dir($snippets_dir) ? 'exists' : 'missing',
        'snippets_writable' => is_writable($snippets_dir) ? 'yes' : 'no',
        'snippets_count' => $php_files_analysis['total_count'],
        'unique_shortcodes' => count($php_files_analysis['by_shortcode']),
        'total_size_bytes' => $php_files_analysis['size_statistics']['total_size'],
        'recent_activity' => !empty($php_files_analysis['recent_files']) ? [
            'most_recent_file' => $php_files_analysis['recent_files'][0]['name'],
            'most_recent_time' => $php_files_analysis['recent_files'][0]['modified_readable'],
            'files_last_hour' => count(array_filter($php_files_analysis['files'], function($f) use ($current_time) {
                return ($current_time - $f['modified']) < 3600;
            }))
        ] : null
    ],
    'write_test' => $write_test,
    'file_analysis' => $php_files_analysis,
    'environment' => $environment_info,
    'api_endpoints' => [
        'health' => $_SERVER['REQUEST_URI'],
        'save_snippet' => '/save-snippet.php',
        'execute_snippet' => '/execute-snippet.php',
        'file_viewer' => '/file-viewer.php'
    ]
];

debug_log("Health check completed", [
    'status' => $overall_status,
    'snippets_count' => $php_files_analysis['total_count'],
    'issues_count' => count($issues)
]);

// Enviar respuesta
http_response_code(200);
echo json_encode($health_response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
