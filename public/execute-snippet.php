<?php
// execute-snippet.php - MOCKS COMPLETOS de WordPress
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Limpiar output previo
if (ob_get_level()) {
    ob_end_clean();
}

// Headers CORS
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
// MOCKS COMPLETOS DE WORDPRESS PARA RENDER
// ================================================================

$GLOBALS['mock_shortcodes'] = [];

// Mock básico de add_shortcode
function add_shortcode($tag, $callback) {
    debug_log("Mock add_shortcode registrado", ['tag' => $tag]);
    $GLOBALS['mock_shortcodes'][$tag] = $callback;
}

// Mock básico de do_shortcode
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

// ================================================================
// MOCKS DE FUNCIONES WORDPRESS MÁS COMUNES
// ================================================================

// Funciones de URL y paths
if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file = '') {
        debug_log("Mock plugin_dir_url called", ['file' => $file]);
        return 'https://lumina.market/wp-content/plugins/drawcode/';
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file = '') {
        debug_log("Mock plugin_dir_path called", ['file' => $file]);
        return '/var/www/html/wp-content/plugins/drawcode/';
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url($path = '', $plugin = '') {
        debug_log("Mock plugins_url called", ['path' => $path, 'plugin' => $plugin]);
        return 'https://lumina.market/wp-content/plugins/' . ltrim($path, '/');
    }
}

if (!function_exists('site_url')) {
    function site_url($path = '', $scheme = null) {
        return 'https://lumina.market' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '', $scheme = null) {
        return 'https://lumina.market' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin') {
        return 'https://lumina.market/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('content_url')) {
    function content_url($path = '') {
        return 'https://lumina.market/wp-content/' . ltrim($path, '/');
    }
}

// Funciones de assets/enqueue
if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle = '', $src = '', $deps = array(), $ver = false, $in_footer = false) {
        debug_log("Mock wp_enqueue_script", ['handle' => $handle, 'src' => $src]);
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle = '', $src = '', $deps = array(), $ver = false, $media = 'all') {
        debug_log("Mock wp_enqueue_style", ['handle' => $handle, 'src' => $src]);
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        debug_log("Mock wp_localize_script", ['handle' => $handle, 'object' => $object_name]);
    }
}

if (!function_exists('wp_register_script')) {
    function wp_register_script($handle, $src, $deps = array(), $ver = false, $in_footer = false) {
        debug_log("Mock wp_register_script", ['handle' => $handle, 'src' => $src]);
    }
}

if (!function_exists('wp_register_style')) {
    function wp_register_style($handle, $src, $deps = array(), $ver = false, $media = 'all') {
        debug_log("Mock wp_register_style", ['handle' => $handle, 'src' => $src]);
    }
}

// Funciones de opciones/configuración
if (!function_exists('get_option')) {
    function get_option($option, $default = false) { 
        debug_log("Mock get_option", ['option' => $option, 'default' => $default]);
        return $default; 
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value, $autoload = null) {
        debug_log("Mock update_option", ['option' => $option, 'value' => $value]);
        return true;
    }
}

if (!function_exists('add_option')) {
    function add_option($option, $value = '', $deprecated = '', $autoload = 'yes') {
        debug_log("Mock add_option", ['option' => $option, 'value' => $value]);
        return true;
    }
}

// Funciones de usuario/permisos
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

if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in() {
        return true;
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

// Funciones de sanitización
if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return strip_tags(trim($str));
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url, $protocols = null, $_context = 'display') {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

// Funciones de nonce/seguridad
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = -1) {
        return 'mock_nonce_' . md5($action . time());
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action = -1) {
        return true; // Siempre válido en mock
    }
}

// Funciones de hooks/actions
if (!function_exists('add_action')) {
    function add_action($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        debug_log("Mock add_action", ['tag' => $tag, 'function' => $function_to_add]);
    }
}

if (!function_exists('add_filter')) {
    function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
        debug_log("Mock add_filter", ['tag' => $tag, 'function' => $function_to_add]);
    }
}

if (!function_exists('do_action')) {
    function do_action($tag, ...$args) {
        debug_log("Mock do_action", ['tag' => $tag]);
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value, ...$args) {
        debug_log("Mock apply_filters", ['tag' => $tag]);
        return $value;
    }
}

// Funciones de AJAX
if (!function_exists('wp_die')) {
    function wp_die($message = '', $title = '', $args = array()) {
        debug_log("Mock wp_die", ['message' => $message]);
        exit($message);
    }
}

if (!function_exists('wp_send_json_success')) {
    function wp_send_json_success($data = null) {
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
}

if (!function_exists('wp_send_json_error')) {
    function wp_send_json_error($data = null) {
        echo json_encode(['success' => false, 'data' => $data]);
        exit;
    }
}

// Definir ABSPATH si no existe
if (!defined('ABSPATH')) {
    define('ABSPATH', '/mock/wordpress/path/');
}

// Definir otras constantes comunes
if (!defined('WP_CONTENT_URL')) {
    define('WP_CONTENT_URL', 'https://lumina.market/wp-content');
}

if (!defined('WP_PLUGIN_URL')) {
    define('WP_PLUGIN_URL', 'https://lumina.market/wp-content/plugins');
}

debug_log("WordPress mocks completos inicializados", [
    'mocks_count' => 35,
    'constants_defined' => ['ABSPATH', 'WP_CONTENT_URL', 'WP_PLUGIN_URL']
]);

// ================================================================
// BÚSQUEDA CORREGIDA POR CONTENIDO (NO POR NOMBRE)
// ================================================================

$snippets_dir = __DIR__ . '/snippets/';
if (!is_dir($snippets_dir)) {
    debug_log("ERROR: Snippets directory missing");
    send_json_response(['success' => false, 'error' => 'Snippets directory not found'], 500);
}

debug_log("🔍 INICIANDO BÚSQUEDA POR CONTENIDO", [
    'shortcode_solicitado' => $shortcode_name,
    'directorio' => $snippets_dir
]);

$snippet_file = null;
$found_by_content = false;
$files = scandir($snippets_dir);
$file_analysis = [];

// ================================================================
// ESTRATEGIA PRINCIPAL: BUSCAR POR CONTENIDO DEL ARCHIVO
// ================================================================

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $file_path = $snippets_dir . $file;
        $file_content = file_get_contents($file_path);
        
        // Buscar el shortcode registrado en el archivo
        if (preg_match('/add_shortcode\s*\(\s*[\'"]([^\'"]+)[\'"]/', $file_content, $matches)) {
            $registered_shortcode = $matches[1];
            
            $analysis = [
                'file' => $file,
                'path' => $file_path,
                'registered_shortcode' => $registered_shortcode,
                'matches_request' => ($registered_shortcode === $shortcode_name),
                'timestamp' => filemtime($file_path),
                'size' => filesize($file_path)
            ];
            
            $file_analysis[] = $analysis;
            
            debug_log("📄 Archivo analizado", $analysis);
            
            // Si el shortcode registrado coincide EXACTAMENTE
            if ($registered_shortcode === $shortcode_name) {
                $snippet_file = $file_path;
                $found_by_content = true;
                
                debug_log("✅ COINCIDENCIA EXACTA POR CONTENIDO", [
                    'archivo' => $file,
                    'shortcode_en_archivo' => $registered_shortcode,
                    'shortcode_solicitado' => $shortcode_name
                ]);
                
                break; // Salir del loop - encontramos el correcto
            }
        } else {
            debug_log("⚠️ Archivo sin shortcode registrado", ['file' => $file]);
        }
    }
}

// ================================================================
// RESULTADO DE LA BÚSQUEDA
// ================================================================

if ($found_by_content && $snippet_file) {
    debug_log("🎯 ARCHIVO ENCONTRADO POR CONTENIDO", [
        'archivo_seleccionado' => basename($snippet_file),
        'método' => 'content_based_search',
        'coincidencia' => 'exacta'
    ]);
} else {
    debug_log("❌ ERROR: No se encontró archivo válido", [
        'shortcode_solicitado' => $shortcode_name,
        'archivos_analizados' => count($file_analysis),
        'coincidencias_exactas' => array_filter($file_analysis, function($a) { return $a['matches_request']; })
    ]);
    
    // Crear lista de shortcodes disponibles
    $available_shortcodes = array_column($file_analysis, 'registered_shortcode');
    $php_files = array_column($file_analysis, 'file');
    
    send_json_response([
        'success' => false,
        'error' => 'No se encontró archivo con shortcode exacto',
        'shortcode_solicitado' => $shortcode_name,
        'shortcodes_disponibles' => $available_shortcodes,
        'archivos_php' => $php_files,
        'análisis_archivos' => $file_analysis,
        'sugerencia' => 'Verificar que el shortcode solicitado coincida exactamente con uno registrado'
    ], 404);
}

debug_log("✅ ARCHIVO FINAL SELECCIONADO", [
    'archivo' => basename($snippet_file),
    'tamaño' => filesize($snippet_file) . ' bytes',
    'método_búsqueda' => $found_by_content ? 'content_exact' : 'name_fallback'
]);

// ================================================================
// EJECUCIÓN SEGURA
// ================================================================

$start_time = microtime(true);

try {
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    include $snippet_file;
    
    $shortcode_executed = false;
    if (isset($GLOBALS['mock_shortcodes'][$shortcode_name])) {
        debug_log("Ejecutando shortcode: " . $shortcode_name);
        
        $callback = $GLOBALS['mock_shortcodes'][$shortcode_name];
        if (is_callable($callback)) {
            ob_clean();
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
    
    // ================================================================
    // DEBUGGING AVANZADO PARA OUTPUT VACÍO
    // ================================================================
    
    if ($shortcode_executed && empty(trim($output))) {
        debug_log("🔍 === DEBUGGING OUTPUT VACÍO ===", [
            'shortcode_name' => $shortcode_name,
            'file_used' => basename($snippet_file),
            'raw_output_length' => strlen($output),
            'raw_output_content' => $output,
            'callback_registered' => isset($GLOBALS['mock_shortcodes'][$shortcode_name]),
            'all_registered_shortcodes' => array_keys($GLOBALS['mock_shortcodes'])
        ]);
        
        // Intentar leer y analizar el archivo directamente
        $file_content = file_get_contents($snippet_file);
        debug_log("📄 Análisis del archivo", [
            'file_size' => strlen($file_content),
            'has_php_opening' => strpos($file_content, '<?php') !== false,
            'has_add_shortcode' => strpos($file_content, 'add_shortcode') !== false,
            'has_ob_start' => strpos($file_content, 'ob_start') !== false,
            'has_ob_get_clean' => strpos($file_content, 'ob_get_clean') !== false
        ]);
        
        // Buscar todas las funciones definidas en el archivo
        preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/', $file_content, $function_matches);
        debug_log("🔧 Funciones encontradas en archivo", [
            'functions' => $function_matches[1] ?? []
        ]);
        
        // Test de ejecución manual de función
        if (!empty($function_matches[1])) {
            debug_log("🧪 Intentando ejecución manual de funciones encontradas");
            
            foreach ($function_matches[1] as $function_name) {
                if (function_exists($function_name) && strpos($function_name, 'shortcode') !== false) {
                    debug_log("🔧 Probando función: " . $function_name);
                    
                    try {
                        ob_start();
                        $manual_output = call_user_func($function_name);
                        $manual_buffer = ob_get_clean();
                        
                        if (!empty($manual_output) || !empty($manual_buffer)) {
                            $output = $manual_output . $manual_buffer;
                            debug_log("🎉 OUTPUT RECUPERADO con ejecución manual", [
                                'function' => $function_name,
                                'output_length' => strlen($output)
                            ]);
                            break;
                        }
                    } catch (Exception $e) {
                        ob_end_clean();
                        debug_log("⚠️ Error en ejecución manual de " . $function_name, [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        
        debug_log("🏁 Final del debugging de output vacío", [
            'output_recovered' => !empty(trim($output)),
            'final_output_length' => strlen($output)
        ]);
    }
    
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
    
    // Limpiar para JSON
    $html = trim($html);
    $css = trim($css);
    $js = trim($js);
    
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
        'mocks_available' => 35,
        'search_method' => 'content_based'
    ];
    
    debug_log("Enviando respuesta exitosa", [
        'html_length' => strlen($html),
        'css_length' => strlen($css),
        'js_length' => strlen($js),
        'search_method' => 'content_based'
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
