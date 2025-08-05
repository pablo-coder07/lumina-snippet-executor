<?php
// execute-snippet.php CORREGIDO - Para ejecutar código WordPress sin WordPress
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers CORS
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

// Función de logging
function debug_log($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] EXECUTE-SNIPPET: {$message}";
    if ($data !== null) {
        $log_entry .= " | Data: " . json_encode($data);
    }
    error_log($log_entry);
}

debug_log("=== NUEVA SOLICITUD DE EJECUCIÓN ===");

// Leer input
$raw_input = file_get_contents('php://input');
if (empty($raw_input)) {
    debug_log("ERROR: Empty input");
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Empty request body']);
    exit;
}

$input = json_decode($raw_input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    debug_log("ERROR: JSON decode failed", ['error' => json_last_error_msg()]);
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$shortcode_name = $input['shortcode'] ?? '';
if (empty($shortcode_name)) {
    debug_log("ERROR: Shortcode missing");
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Shortcode required']);
    exit;
}

debug_log("Processing shortcode: " . $shortcode_name);

// ================================================================
// MOCK DE FUNCIONES WORDPRESS PARA RENDER
// ================================================================

// Variables globales para simular WordPress
$GLOBALS['mock_shortcodes'] = [];
$GLOBALS['mock_output'] = '';

// Mock de add_shortcode - En lugar de registrar, ejecutamos directamente
function add_shortcode($tag, $callback) {
    debug_log("Mock add_shortcode called", ['tag' => $tag, 'callback' => $callback]);
    $GLOBALS['mock_shortcodes'][$tag] = $callback;
}

// Mock de do_shortcode - Ejecuta el shortcode mock
function do_shortcode($content) {
    // Buscar shortcodes en el contenido
    if (preg_match('/\[([^\]]+)\]/', $content, $matches)) {
        $shortcode = $matches[1];
        if (isset($GLOBALS['mock_shortcodes'][$shortcode])) {
            $callback = $GLOBALS['mock_shortcodes'][$shortcode];
            if (is_callable($callback)) {
                return call_user_func($callback);
            }
        }
    }
    return $content;
}

// Mock de otras funciones WordPress comunes
function defined($name) {
    if ($name === 'ABSPATH') return true;
    return \defined($name);
}

function wp_enqueue_script() { /* No-op */ }
function wp_enqueue_style() { /* No-op */ }
function wp_localize_script() { /* No-op */ }
function get_option($option, $default = false) { return $default; }
function is_admin() { return false; }
function current_user_can($capability) { return true; }

debug_log("WordPress mocks initialized");

// ================================================================
// BUSCAR Y EJECUTAR ARCHIVO
// ================================================================

$snippets_dir = __DIR__ . '/snippets/';
if (!is_dir($snippets_dir)) {
    debug_log("ERROR: Snippets directory missing");
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Snippets directory not found']);
    exit;
}

// Buscar archivo
$snippet_file = null;
$latest_timestamp = 0;
$files = scandir($snippets_dir);

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $patterns = [
            '/^' . preg_quote($shortcode_name, '/') . '_(\d+)\.php$/',
            '/^' . preg_quote($shortcode_name, '/') . '_v\d+_(\d+)\.php$/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $file, $matches)) {
                $file_timestamp = intval($matches[1]);
                if ($file_timestamp > $latest_timestamp) {
                    $latest_timestamp = $file_timestamp;
                    $snippet_file = $snippets_dir . $file;
                }
                debug_log("Found candidate: " . $file . " (timestamp: " . $file_timestamp . ")");
                break;
            }
        }
    }
}

if (!$snippet_file || !file_exists($snippet_file)) {
    debug_log("ERROR: Snippet file not found", ['shortcode' => $shortcode_name]);
    
    $php_files = array_filter($files, function($f) {
        return pathinfo($f, PATHINFO_EXTENSION) === 'php';
    });
    
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Snippet not found',
        'shortcode' => $shortcode_name,
        'available_files' => array_values($php_files)
    ]);
    exit;
}

debug_log("Executing file: " . basename($snippet_file));

// ================================================================
// EJECUCIÓN CON CAPTURA DE OUTPUT
// ================================================================

$start_time = microtime(true);
$execution_error = '';

try {
    // Capturar output
    ob_start();
    
    // Incluir el archivo (que registrará el shortcode via add_shortcode mock)
    include $snippet_file;
    
    // Si el archivo definió un shortcode, ejecutarlo
    $shortcode_executed = false;
    if (isset($GLOBALS['mock_shortcodes'][$shortcode_name])) {
        debug_log("Shortcode found in mocks, executing: " . $shortcode_name);
        
        $callback = $GLOBALS['mock_shortcodes'][$shortcode_name];
        if (is_callable($callback)) {
            $shortcode_output = call_user_func($callback);
            echo $shortcode_output;
            $shortcode_executed = true;
            debug_log("Shortcode executed successfully");
        }
    }
    
    $output = ob_get_clean();
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
    
    if (!$shortcode_executed) {
        debug_log("WARNING: Shortcode not executed via callback, using direct output");
    }
    
    debug_log("Execution completed", [
        'output_length' => strlen($output),
        'execution_time' => $execution_time . 'ms',
        'shortcode_executed' => $shortcode_executed
    ]);
    
    // Procesar output
    $html = $output;
    $css = '';
    $js = '';
    
    // Extraer CSS
    if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $output, $css_matches)) {
        $css = implode("\n", $css_matches[1]);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
    }
    
    // Extraer JavaScript
    if (preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $html, $js_matches)) {
        $js = implode("\n", $js_matches[1]);
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
    }
    
    // Respuesta exitosa
    $response = [
        'success' => true,
        'html' => trim($html),
        'css' => trim($css),
        'js' => trim($js),
        'execution_time' => $execution_time,
        'file_used' => basename($snippet_file),
        'shortcode_executed' => $shortcode_executed,
        'mock_shortcodes_registered' => array_keys($GLOBALS['mock_shortcodes']),
        'debug_info' => [
            'timestamp' => time(),
            'file_size' => filesize($snippet_file),
            'execution_method' => $shortcode_executed ? 'callback' : 'direct_output'
        ]
    ];
    
    debug_log("Returning success response", [
        'html_length' => strlen($response['html']),
        'css_length' => strlen($response['css']),
        'js_length' => strlen($response['js'])
    ]);
    
    echo json_encode($response);
    
} catch (ParseError $e) {
    ob_end_clean();
    $error = "Parse Error: " . $e->getMessage() . " in line " . $e->getLine();
    debug_log("Parse error", ['error' => $error]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $error,
        'error_type' => 'parse_error',
        'file_used' => basename($snippet_file)
    ]);
    
} catch (Error $e) {
    ob_end_clean();
    $error = "Fatal Error: " . $e->getMessage() . " in line " . $e->getLine();
    debug_log("Fatal error", ['error' => $error]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $error,
        'error_type' => 'fatal_error',
        'file_used' => basename($snippet_file)
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    $error = "Exception: " . $e->getMessage();
    debug_log("Exception", ['error' => $error]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $error,
        'error_type' => 'exception',
        'file_used' => basename($snippet_file)
    ]);
}
?>
