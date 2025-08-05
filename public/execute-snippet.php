<?php
// execute-snippet.php CORREGIDO - Mejorado para debugging y mejor manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers CORS mejorados
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, X-Request-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Función de logging mejorada
function debug_log($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] EXECUTE-SNIPPET: {$message}";
    if ($data !== null) {
        $log_entry .= " | Data: " . json_encode($data);
    }
    error_log($log_entry);
}

debug_log("=== NUEVA SOLICITUD DE EJECUCIÓN ===");
debug_log("Request method: " . $_SERVER['REQUEST_METHOD']);
debug_log("Content type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

// Leer input con mejor manejo de errores
$raw_input = file_get_contents('php://input');
debug_log("Raw input length: " . strlen($raw_input));

if (empty($raw_input)) {
    debug_log("ERROR: Empty input received");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Empty request body',
        'debug_info' => [
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not set',
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'headers' => getallheaders()
        ]
    ]);
    exit;
}

$input = json_decode($raw_input, true);
$json_error = json_last_error();

if ($json_error !== JSON_ERROR_NONE) {
    debug_log("ERROR: JSON decode failed", [
        'error' => json_last_error_msg(),
        'raw_input_preview' => substr($raw_input, 0, 200)
    ]);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON: ' . json_last_error_msg(),
        'raw_input_preview' => substr($raw_input, 0, 200)
    ]);
    exit;
}

debug_log("Input decoded successfully", [
    'keys' => array_keys($input),
    'shortcode' => $input['shortcode'] ?? 'not set'
]);

// Validar shortcode
$shortcode_name = $input['shortcode'] ?? '';
if (empty($shortcode_name)) {
    debug_log("ERROR: Shortcode missing", [
        'received_fields' => array_keys($input),
        'input_sample' => array_slice($input, 0, 3, true)
    ]);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Shortcode name required',
        'received_fields' => array_keys($input),
        'expected_field' => 'shortcode'
    ]);
    exit;
}

debug_log("Processing shortcode: " . $shortcode_name);

// Configurar directorio de snippets
$snippets_dir = __DIR__ . '/snippets/';
debug_log("Snippets directory: " . $snippets_dir);

// Verificar directorio
if (!is_dir($snippets_dir)) {
    debug_log("CRITICAL: Snippets directory missing", [
        'expected_path' => $snippets_dir,
        'current_dir' => __DIR__,
        'current_dir_contents' => scandir(__DIR__)
    ]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Snippets directory not found',
        'directory' => $snippets_dir,
        'current_dir' => __DIR__,
        'suggestion' => 'Check if snippets directory exists'
    ]);
    exit;
}

// Buscar archivo del shortcode con mejor algoritmo
$snippet_file = null;
$latest_timestamp = 0;
$candidates = [];

$files = @scandir($snippets_dir);
if (!$files) {
    debug_log("ERROR: Cannot read snippets directory");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Cannot read snippets directory',
        'directory' => $snippets_dir
    ]);
    exit;
}

debug_log("Scanning " . count($files) . " files for shortcode: " . $shortcode_name);

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $full_path = $snippets_dir . $file;
        
        // Patrones de búsqueda mejorados
        $patterns = [
            // Patrón básico: shortcode_timestamp.php
            '/^' . preg_quote($shortcode_name, '/') . '_(\d+)\.php$/',
            // Patrón versionado: shortcode_v1_timestamp.php
            '/^' . preg_quote($shortcode_name, '/') . '_v\d+_(\d+)\.php$/',
            // Patrón alternativo: shortcode-timestamp.php
            '/^' . preg_quote($shortcode_name, '/') . '-(\d+)\.php$/'
        ];
        
        foreach ($patterns as $pattern_index => $pattern) {
            if (preg_match($pattern, $file, $matches)) {
                $file_timestamp = intval($matches[1]);
                
                $candidate = [
                    'file' => $file,
                    'path' => $full_path,
                    'timestamp' => $file_timestamp,
                    'pattern' => $pattern_index,
                    'size' => filesize($full_path),
                    'modified' => date('Y-m-d H:i:s', filemtime($full_path))
                ];
                
                $candidates[] = $candidate;
                
                if ($file_timestamp > $latest_timestamp) {
                    $latest_timestamp = $file_timestamp;
                    $snippet_file = $full_path;
                }
                
                debug_log("Found candidate", $candidate);
                break;
            }
        }
    }
}

debug_log("Search completed", [
    'candidates_found' => count($candidates),
    'latest_timestamp' => $latest_timestamp,
    'selected_file' => $snippet_file ? basename($snippet_file) : 'none'
]);

if (!$snippet_file || !file_exists($snippet_file)) {
    // Información detallada para debugging
    $php_files = array_filter($files, function($f) {
        return pathinfo($f, PATHINFO_EXTENSION) === 'php';
    });
    
    debug_log("Snippet not found", [
        'shortcode' => $shortcode_name,
        'candidates_found' => count($candidates),
        'total_php_files' => count($php_files),
        'php_files' => array_values($php_files)
    ]);
    
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Snippet not found',
        'shortcode' => $shortcode_name,
        'directory_searched' => $snippets_dir,
        'candidates_found' => $candidates,
        'php_files_available' => array_values($php_files),
        'search_patterns_used' => [
            'basic' => $shortcode_name . '_TIMESTAMP.php',
            'versioned' => $shortcode_name . '_vN_TIMESTAMP.php',
            'alternative' => $shortcode_name . '-TIMESTAMP.php'
        ]
    ]);
    exit;
}

debug_log("Executing snippet", [
    'file' => basename($snippet_file),
    'path' => $snippet_file,
    'size' => filesize($snippet_file) . ' bytes'
]);

// EJECUCIÓN CON MANEJO ROBUSTO DE ERRORES
$start_time = microtime(true);
$execution_error = '';
$output = '';

// Configurar manejo de errores personalizado
set_error_handler(function($severity, $message, $file, $line) use (&$execution_error) {
    $execution_error = "PHP Error [$severity]: $message in $file on line $line";
    return true;
});

// Capturar errores fatales
register_shutdown_function(function() use (&$execution_error, $snippet_file) {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $execution_error = "Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'];
        debug_log("Fatal error captured", [
            'error' => $execution_error,
            'file' => basename($snippet_file)
        ]);
    }
});

try {
    ob_start();
    
    // Incluir el archivo
    include $snippet_file;
    
    $output = ob_get_clean();
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
    
    // Restaurar manejo de errores
    restore_error_handler();
    
    // Verificar si hubo errores durante la ejecución
    if (!empty($execution_error)) {
        throw new Exception($execution_error);
    }
    
    debug_log("Snippet executed successfully", [
        'execution_time' => $execution_time . 'ms',
        'output_length' => strlen($output),
        'file' => basename($snippet_file)
    ]);
    
    // Procesar output para separar HTML, CSS y JS
    $html = $output;
    $css = '';
    $js = '';
    
    // Extraer y remover CSS
    if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $output, $css_matches)) {
        $css = implode("\n", $css_matches[1]);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        debug_log("CSS extracted", ['css_length' => strlen($css)]);
    }
    
    // Extraer y remover JavaScript
    if (preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $html, $js_matches)) {
        $js = implode("\n", $js_matches[1]);
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        debug_log("JavaScript extracted", ['js_length' => strlen($js)]);
    }
    
    // Limpiar output buffer residual
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Preparar respuesta exitosa
    $response = [
        'success' => true,
        'html' => trim($html),
        'css' => trim($css),
        'js' => trim($js),
        'execution_time' => $execution_time,
        'file_used' => basename($snippet_file),
        'directory_used' => $snippets_dir,
        'timestamp' => time(),
        'debug_info' => [
            'candidates_checked' => count($candidates),
            'file_size' => filesize($snippet_file),
            'last_modified' => filemtime($snippet_file)
        ]
    ];
    
    debug_log("Returning successful response", [
        'html_length' => strlen($response['html']),
        'css_length' => strlen($response['css']),
        'js_length' => strlen($response['js'])
    ]);
    
    // Enviar respuesta JSON
    echo json_encode($response);
    
} catch (ParseError $e) {
    ob_end_clean();
    restore_error_handler();
    
    $error_msg = "Parse Error: " . $e->getMessage() . " in line " . $e->getLine();
    debug_log("Parse error", ['error' => $error_msg, 'file' => basename($snippet_file)]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $error_msg,
        'file_used' => basename($snippet_file),
        'error_type' => 'parse_error',
        'line' => $e->getLine()
    ]);
    
} catch (Error $e) {
    ob_end_clean();
    restore_error_handler();
    
    $error_msg = "Fatal Error: " . $e->getMessage() . " in line " . $e->getLine();
    debug_log("Fatal error", ['error' => $error_msg, 'file' => basename($snippet_file)]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $error_msg,
        'file_used' => basename($snippet_file),
        'error_type' => 'fatal_error',
        'line' => $e->getLine()
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    restore_error_handler();
    
    $error_msg = "Exception: " . $e->getMessage();
    debug_log("Exception", ['error' => $error_msg, 'file' => basename($snippet_file)]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $error_msg,
        'file_used' => basename($snippet_file),
        'error_type' => 'exception'
    ]);
}
?>
