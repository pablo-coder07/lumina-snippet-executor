<?php
// execute-snippet.php CORREGIDO FINAL - Sin redeclarar funciones nativas
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Limpiar cualquier output previo
if (ob_get_level()) {
    ob_end_clean();
}

// Headers CORS específicos
header('Access-Control-Allow-Origin: https://lumina.market');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, X-Request-ID, User-Agent');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

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

// Función para respuesta JSON limpia
function send_json_response($data, $status_code = 200) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code($status_code);
    header('Access-Control-Allow-Origin: https://lumina.market');
    header('Content-Type: application/json; charset=utf-8');
    
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_response = [
            'success' => false, 
            'error' => 'JSON encoding failed: ' . json_last_error_msg(),
            'timestamp' => time()
        ];
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
// MOCKS WORDPRESS CORREGIDOS - SIN REDECLARACIONES
// ================================================================

$GLOBALS['mock_shortcodes'] = [];

// Mock de add_shortcode - registra en global
function add_shortcode($tag, $callback) {
    debug_log("Mock add_shortcode registrado", ['tag' => $tag]);
    $GLOBALS['mock_shortcodes'][$tag] = $callback;
}

// Mock de do_shortcode - ejecuta si existe
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

// Mock de funciones WordPress SIN REDECLARAR NATIVAS
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script() { /* No-op */ }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style() { /* No-op */ }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script() { /* No-op */ }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) { 
        return $default; 
    }
}

if (!function_exists('is_admin')) {
    function is_admin() { 
        return false; 
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) { 
        return true; 
    }
}

// SOLUCIÓN PARA defined() - NO redeclarar, usar mock ABSPATH
if (!defined('ABSPATH')) {
    define('ABSPATH', '/mock/wordpress/path/');
}

debug_log("WordPress mocks inicializados correctamente");

// ================================================================
// BÚSQUEDA DE ARCHIVO
// ================================================================

$snippets_dir = __DIR__ . '/snippets/';
if (!is_dir($snippets_dir)) {
    debug_log("ERROR: Snippets directory missing");
    send_json_response(['success' => false, 'error' => 'Snippets directory not found'], 500);
}

$snippet_file = null;
$latest_timestamp = 0;
$candidates = [];
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
                $candidate = [
                    'file' => $file,
                    'timestamp' => $file_timestamp,
                    'path' => $snippets_dir . $file
                ];
                
                $candidates[] = $candidate;
                
                if ($file_timestamp > $latest_timestamp) {
                    $latest_timestamp = $file_timestamp;
                    $snippet_file = $snippets_dir . $file;
                }
                
                debug_log("Candidato encontrado", $candidate);
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
        'candidates_found' => $candidates,
        'available_files' => array_values($php_files)
    ], 404);
}

debug_log("Ejecutando archivo", ['file' => basename($snippet_file), 'size' => filesize($snippet_file)]);

// ================================================================
// EJECUCIÓN SEGURA CON MOCKS
// ================================================================

$start_time = microtime(true);

try {
    // Limpiar buffers de salida
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    // Incluir el archivo PHP
    include $snippet_file;
    
    // Ejecutar shortcode si fue registrado
    $shortcode_executed = false;
    if (isset($GLOBALS['mock_shortcodes'][$shortcode_name])) {
        debug_log("Ejecutando shortcode: " . $shortcode_name);
        
        $callback = $GLOBALS['mock_shortcodes'][$shortcode_name];
        if (is_callable($callback)) {
            ob_clean(); // Limpiar output del include
            $execution_output = call_user_func($callback);
            echo $execution_output;
            $shortcode_executed = true;
        }
    }
    
    $output = ob_get_clean();
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
    
    debug_log("Ejecución completada", [
        'output_length' => strlen($output),
        'execution_time' => $execution_time . 'ms',
        'shortcode_executed' => $shortcode_executed
    ]);
    
    // Procesar output
    $html = trim($output);
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
    
    // Limpiar y validar para JSON
    $html = trim($html);
    $css = trim($css);
    $js = trim($js);
    
    // Validar UTF-8
    if (!mb_check_encoding($html, 'UTF-8')) {
        $html = mb_convert_encoding($html, 'UTF-8', 'auto');
    }
    if (!mb_check_encoding($css, 'UTF-8')) {
        $css = mb_convert_encoding($css, 'UTF-8', 'auto');
    }
    if (!mb_check_encoding($js, 'UTF-8')) {
        $js = mb_convert_encoding($js, 'UTF-8', 'auto');
    }
    
    // Respuesta exitosa
    $response = [
        'success' => true,
        'html' => $html,
        'css' => $css,
        'js' => $js,
        'execution_time' => $execution_time,
        'file_used' => basename($snippet_file),
        'shortcode_executed' => $shortcode_executed,
        'remote_execution' => true,
        'timestamp' => time(),
        'mock_shortcodes_registered' => array_keys($GLOBALS['mock_shortcodes']),
        'debug_info' => [
            'file_size' => filesize($snippet_file),
            'candidates_found' => count($candidates)
        ]
    ];
    
    debug_log("Enviando respuesta exitosa", [
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
