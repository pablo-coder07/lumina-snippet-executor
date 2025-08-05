<?php
// execute-snippet.php CORREGIDO - JSON sin errores de sintaxis
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers CORS - IMPORTANTE: Limpiar cualquier output previo
if (ob_get_level()) {
    ob_end_clean();
}

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
        $log_entry .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    error_log($log_entry);
}

// Función para enviar respuesta JSON limpia
function send_json_response($data, $status_code = 200) {
    // Limpiar cualquier output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code($status_code);
    
    // Asegurar que no hay caracteres extra
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Si hay error en JSON, enviar error simple
        $error_response = ['success' => false, 'error' => 'JSON encoding failed: ' . json_last_error_msg()];
        echo json_encode($error_response);
    } else {
        echo $json;
    }
    exit;
}

debug_log("=== NUEVA SOLICITUD DE EJECUCIÓN ===");

// Leer input
$raw_input = file_get_contents('php://input');
if (empty($raw_input)) {
    debug_log("ERROR: Empty input");
    send_json_response(['success' => false, 'error' => 'Empty request body'], 400);
}

$input = json_decode($raw_input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    debug_log("ERROR: JSON decode failed", ['error' => json_last_error_msg()]);
    send_json_response(['success' => false, 'error' => 'Invalid JSON input'], 400);
}

$shortcode_name = $input['shortcode'] ?? '';
if (empty($shortcode_name)) {
    debug_log("ERROR: Shortcode missing");
    send_json_response(['success' => false, 'error' => 'Shortcode required'], 400);
}

debug_log("Processing shortcode: " . $shortcode_name);

// ================================================================
// MOCK DE FUNCIONES WORDPRESS PARA RENDER
// ================================================================

$GLOBALS['mock_shortcodes'] = [];

function add_shortcode($tag, $callback) {
    debug_log("Mock add_shortcode called", ['tag' => $tag]);
    $GLOBALS['mock_shortcodes'][$tag] = $callback;
}

function do_shortcode($content) {
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

// Mock de otras funciones WordPress
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
// BUSCAR ARCHIVO
// ================================================================

$snippets_dir = __DIR__ . '/snippets/';
if (!is_dir($snippets_dir)) {
    debug_log("ERROR: Snippets directory missing");
    send_json_response(['success' => false, 'error' => 'Snippets directory not found'], 500);
}

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
                debug_log("Found candidate: " . $file);
                break;
            }
        }
    }
}

if (!$snippet_file || !file_exists($snippet_file)) {
    debug_log("ERROR: Snippet file not found");
    
    $php_files = array_filter($files, function($f) {
        return pathinfo($f, PATHINFO_EXTENSION) === 'php';
    });
    
    send_json_response([
        'success' => false,
        'error' => 'Snippet not found',
        'shortcode' => $shortcode_name,
        'available_files' => array_values($php_files)
    ], 404);
}

debug_log("Executing file: " . basename($snippet_file));

// ================================================================
// EJECUCIÓN SEGURA CON CAPTURA DE OUTPUT
// ================================================================

$start_time = microtime(true);

try {
    // Iniciar captura de output con buffer limpio
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    // Incluir archivo PHP
    include $snippet_file;
    
    // Ejecutar shortcode si existe
    $shortcode_executed = false;
    if (isset($GLOBALS['mock_shortcodes'][$shortcode_name])) {
        debug_log("Executing shortcode callback: " . $shortcode_name);
        
        $callback = $GLOBALS['mock_shortcodes'][$shortcode_name];
        if (is_callable($callback)) {
            $shortcode_output = call_user_func($callback);
            
            // Limpiar buffer anterior y usar solo el output del shortcode
            ob_clean();
            echo $shortcode_output;
            $shortcode_executed = true;
        }
    }
    
    $output = ob_get_clean();
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
    
    debug_log("Execution completed", [
        'output_length' => strlen($output),
        'execution_time' => $execution_time . 'ms',
        'shortcode_executed' => $shortcode_executed
    ]);
    
    // Procesar output de forma segura
    $html = $output;
    $css = '';
    $js = '';
    
    // Extraer CSS de forma segura
    if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $output, $css_matches)) {
        $css = implode("\n", $css_matches[1]);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
    }
    
    // Extraer JavaScript de forma segura
    if (preg_match_all('/<script[^>]*>(.*?)<\/script>/is', $html, $js_matches)) {
        $js = implode("\n", $js_matches[1]);
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
    }
    
    // Limpiar strings para JSON seguro
    $html = trim($html);
    $css = trim($css);
    $js = trim($js);
    
    // Verificar que los strings son válidos para JSON
    if (!mb_check_encoding($html, 'UTF-8')) {
        $html = mb_convert_encoding($html, 'UTF-8', 'auto');
    }
    if (!mb_check_encoding($css, 'UTF-8')) {
        $css = mb_convert_encoding($css, 'UTF-8', 'auto');
    }
    if (!mb_check_encoding($js, 'UTF-8')) {
        $js = mb_convert_encoding($js, 'UTF-8', 'auto');
    }
    
    // Respuesta final
    $response = [
        'success' => true,
        'html' => $html,
        'css' => $css,
        'js' => $js,
        'execution_time' => $execution_time,
        'file_used' => basename($snippet_file),
        'shortcode_executed' => $shortcode_executed,
        'timestamp' => time()
    ];
    
    debug_log("Sending success response", [
        'html_length' => strlen($html),
        'css_length' => strlen($css),
        'js_length' => strlen($js)
    ]);
    
    send_json_response($response);
    
} catch (ParseError $e) {
    ob_end_clean();
    debug_log("Parse error", ['error' => $e->getMessage(), 'line' => $e->getLine()]);
    
    send_json_response([
        'success' => false,
        'error' => 'Parse Error: ' . $e->getMessage(),
        'error_type' => 'parse_error',
        'line' => $e->getLine(),
        'file_used' => basename($snippet_file)
    ], 500);
    
} catch (Error $e) {
    ob_end_clean();
    debug_log("Fatal error", ['error' => $e->getMessage(), 'line' => $e->getLine()]);
    
    send_json_response([
        'success' => false,
        'error' => 'Fatal Error: ' . $e->getMessage(),
        'error_type' => 'fatal_error',
        'line' => $e->getLine(),
        'file_used' => basename($snippet_file)
    ], 500);
    
} catch (Exception $e) {
    ob_end_clean();
    debug_log("Exception", ['error' => $e->getMessage()]);
    
    send_json_response([
        'success' => false,
        'error' => 'Exception: ' . $e->getMessage(),
        'error_type' => 'exception',
        'file_used' => basename($snippet_file)
    ], 500);
}
?>
