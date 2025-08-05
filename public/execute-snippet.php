<?php
// execute-snippet.php CORREGIDO - Con CORS explícito para lumina.market
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// CORS HEADERS ESPECÍFICOS PARA LUMINA.MARKET
header('Access-Control-Allow-Origin: https://lumina.market');
header('Access-Control-Allow-Methods: POST, OPTIONS, GET');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, X-Request-ID, User-Agent');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// LOG DE DEBUGGING PARA CORS
error_log("=== REQUEST DEBUG ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Origin: " . ($_SERVER['HTTP_ORIGIN'] ?? 'not set'));
error_log("User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'not set'));
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set'));

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    error_log("CORS preflight request handled");
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed', 'method' => $_SERVER['REQUEST_METHOD']]);
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
    
    // Headers adicionales para asegurar CORS
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

// Leer input con debugging extendido
$raw_input = file_get_contents('php://input');
debug_log("Raw input received", ['length' => strlen($raw_input)]);

if (empty($raw_input)) {
    debug_log("ERROR: Empty input");
    send_json_response([
        'success' => false, 
        'error' => 'Empty request body',
        'debug_info' => [
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not set',
            'method' => $_SERVER['REQUEST_METHOD']
        ]
    ], 400);
}

$input = json_decode($raw_input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    debug_log("ERROR: JSON decode failed", [
        'error' => json_last_error_msg(),
        'raw_preview' => substr($raw_input, 0, 100)
    ]);
    send_json_response([
        'success' => false, 
        'error' => 'Invalid JSON input: ' . json_last_error_msg(),
        'raw_preview' => substr($raw_input, 0, 100)
    ], 400);
}

$shortcode_name = $input['shortcode'] ?? '';
if (empty($shortcode_name)) {
    debug_log("ERROR: Shortcode missing");
    send_json_response([
        'success' => false, 
        'error' => 'Shortcode required',
        'received_fields' => array_keys($input)
    ], 400);
}

debug_log("Processing shortcode: " . $shortcode_name);

// ================================================================
// SISTEMA DE COMUNICACIÓN A DISTANCIA - MOCKS WORDPRESS
// ================================================================

$GLOBALS['mock_shortcodes'] = [];

function add_shortcode($tag, $callback) {
    debug_log("Mock add_shortcode registrado", ['tag' => $tag]);
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

// Mocks de funciones WordPress esenciales
function defined($name) {
    if ($name === 'ABSPATH') return true;
    return \defined($name);
}

function wp_enqueue_script() { }
function wp_enqueue_style() { }
function wp_localize_script() { }
function get_option($option, $default = false) { return $default; }
function is_admin() { return false; }
function current_user_can($capability) { return true; }

debug_log("Sistema de comunicación a distancia inicializado");

// ================================================================
// BÚSQUEDA DEL CÓDIGO EN RENDER
// ================================================================

$snippets_dir = __DIR__ . '/snippets/';
debug_log("Buscando en directorio remoto", ['path' => $snippets_dir]);

if (!is_dir($snippets_dir)) {
    debug_log("ERROR CRÍTICO: Directorio snippets no existe en Render");
    send_json_response([
        'success' => false, 
        'error' => 'Remote snippets directory not found',
        'expected_path' => $snippets_dir,
        'current_dir' => __DIR__
    ], 500);
}

// Buscar archivo específico del shortcode
$snippet_file = null;
$latest_timestamp = 0;
$candidates = [];
$files = scandir($snippets_dir);

debug_log("Archivos encontrados en Render", ['total_files' => count($files)]);

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        // Patrones de búsqueda para archivos de shortcode
        $patterns = [
            '/^' . preg_quote($shortcode_name, '/') . '_(\d+)\.php$/',           // shortcode_timestamp.php
            '/^' . preg_quote($shortcode_name, '/') . '_v\d+_(\d+)\.php$/',     // shortcode_v1_timestamp.php
            '/^' . preg_quote($shortcode_name, '/') . '-(\d+)\.php$/'           // shortcode-timestamp.php
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
    debug_log("ERROR: Código no encontrado en Render", [
        'shortcode' => $shortcode_name,
        'candidates_found' => count($candidates),
        'searched_patterns' => [
            $shortcode_name . '_TIMESTAMP.php',
            $shortcode_name . '_vN_TIMESTAMP.php',
            $shortcode_name . '-TIMESTAMP.php'
        ]
    ]);
    
    $php_files = array_filter($files, function($f) {
        return pathinfo($f, PATHINFO_EXTENSION) === 'php';
    });
    
    send_json_response([
        'success' => false,
        'error' => 'Remote code not found for shortcode',
        'shortcode' => $shortcode_name,
        'candidates_found' => $candidates,
        'available_php_files' => array_values($php_files),
        'search_location' => $snippets_dir
    ], 404);
}

debug_log("Ejecutando código remoto", [
    'file' => basename($snippet_file),
    'size' => filesize($snippet_file) . ' bytes'
]);

// ================================================================
// EJECUCIÓN REMOTA DEL CÓDIGO
// ================================================================

$start_time = microtime(true);

try {
    // Limpiar buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    // PASO CRÍTICO: Incluir y ejecutar el código PHP desde Render
    include $snippet_file;
    
    // Ejecutar shortcode si fue registrado
    $shortcode_executed = false;
    $execution_output = '';
    
    if (isset($GLOBALS['mock_shortcodes'][$shortcode_name])) {
        debug_log("Ejecutando shortcode remoto: " . $shortcode_name);
        
        $callback = $GLOBALS['mock_shortcodes'][$shortcode_name];
        if (is_callable($callback)) {
            ob_clean(); // Limpiar output anterior
            $execution_output = call_user_func($callback);
            echo $execution_output;
            $shortcode_executed = true;
            debug_log("Shortcode remoto ejecutado exitosamente");
        }
    }
    
    $output = ob_get_clean();
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
    
    debug_log("Ejecución remota completada", [
        'output_length' => strlen($output),
        'execution_time' => $execution_time . 'ms',
        'shortcode_executed' => $shortcode_executed
    ]);
    
    // Procesar resultado para envío a WordPress
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
    
    // Limpiar y validar strings para JSON
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
    
    // RESPUESTA EXITOSA DE COMUNICACIÓN REMOTA
    $response = [
        'success' => true,
        'html' => $html,
        'css' => $css,
        'js' => $js,
        'execution_time' => $execution_time,
        'file_used' => basename($snippet_file),
        'shortcode_executed' => $shortcode_executed,
        'remote_execution' => true,
        'render_location' => $snippets_dir,
        'timestamp' => time(),
        'communication_status' => 'successful'
    ];
    
    debug_log("Enviando respuesta exitosa a WordPress", [
        'html_length' => strlen($html),
        'css_length' => strlen($css),
        'js_length' => strlen($js),
        'communication' => 'success'
    ]);
    
    send_json_response($response);
    
} catch (ParseError $e) {
    ob_end_clean();
    debug_log("Error de sintaxis en código remoto", [
        'error' => $e->getMessage(), 
        'line' => $e->getLine(),
        'file' => basename($snippet_file)
    ]);
    
    send_json_response([
        'success' => false,
        'error' => 'Remote code parse error: ' . $e->getMessage(),
        'error_type' => 'parse_error',
        'line' => $e->getLine(),
        'file_used' => basename($snippet_file),
        'communication_status' => 'failed'
    ], 500);
    
} catch (Error $e) {
    ob_end_clean();
    debug_log("Error fatal en código remoto", [
        'error' => $e->getMessage(), 
        'line' => $e->getLine()
    ]);
    
    send_json_response([
        'success' => false,
        'error' => 'Remote code fatal error: ' . $e->getMessage(),
        'error_type' => 'fatal_error',
        'line' => $e->getLine(),
        'file_used' => basename($snippet_file),
        'communication_status' => 'failed'
    ], 500);
    
} catch (Exception $e) {
    ob_end_clean();
    debug_log("Excepción en ejecución remota", ['error' => $e->getMessage()]);
    
    send_json_response([
        'success' => false,
        'error' => 'Remote execution exception: ' . $e->getMessage(),
        'error_type' => 'exception',
        'file_used' => basename($snippet_file),
        'communication_status' => 'failed'
    ], 500);
}
?>
