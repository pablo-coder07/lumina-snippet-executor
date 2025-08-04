<?php
// execute-snippet.php CORREGIDO - Para coincidir con lo que envía WordPress
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
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

error_log("Execute snippet request received at " . date('Y-m-d H:i:s'));

$input = json_decode(file_get_contents('php://input'), true);

// LOG DE DEBUG PARA VER QUÉ RECIBE
error_log("=== EXECUTE SNIPPET DEBUG ===");
error_log("Input recibido: " . json_encode($input));

if (!$input) {
    error_log("ERROR: No se pudo decodificar JSON");
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// CORREGIDO: WordPress envía 'shortcode', no 'shortcode' y 'code'
$shortcode_name = $input['shortcode'] ?? '';

if (empty($shortcode_name)) {
    error_log("ERROR: Shortcode name missing from input");
    error_log("Available fields: " . implode(', ', array_keys($input)));
    http_response_code(400);
    echo json_encode([
        'error' => 'Shortcode name required',
        'received_fields' => array_keys($input),
        'expected_field' => 'shortcode'
    ]);
    exit;
}

error_log("Looking for shortcode: " . $shortcode_name);

// BUSCAR DIRECTAMENTE EN SNIPPETS
$snippets_dir = __DIR__ . '/snippets/';

error_log("Searching in directory: " . $snippets_dir);

// Verificar que el directorio existe
if (!is_dir($snippets_dir)) {
    error_log("CRITICAL: Snippets directory does not exist: " . $snippets_dir);
    http_response_code(500);
    echo json_encode([
        'error' => 'Snippets directory not found',
        'directory' => $snippets_dir,
        'current_dir' => __DIR__
    ]);
    exit;
}

$snippet_file = null;
$latest_timestamp = 0;

$files = @scandir($snippets_dir);
if (!$files) {
    error_log("Cannot read snippets directory");
    http_response_code(500);
    echo json_encode(['error' => 'Cannot read snippets directory']);
    exit;
}

foreach ($files as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        // Buscar archivos que coincidan con el shortcode
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
                error_log("Found candidate file: " . $file . " (timestamp: " . $file_timestamp . ")");
                break;
            }
        }
    }
}

if (!$snippet_file || !file_exists($snippet_file)) {
    error_log("Snippet file not found for: " . $shortcode_name);
    
    // Información de debugging
    $php_files = array_filter($files, function($f) {
        return pathinfo($f, PATHINFO_EXTENSION) === 'php';
    });
    
    error_log("Available PHP files: " . implode(', ', $php_files));
    
    http_response_code(404);
    echo json_encode([
        'error' => 'Snippet not found',
        'shortcode' => $shortcode_name,
        'directory_searched' => $snippets_dir,
        'php_files_found' => array_values($php_files),
        'total_files' => count($files)
    ]);
    exit;
}

error_log("Executing snippet file: " . basename($snippet_file));

// EJECUCIÓN CON CAPTURA DE ERRORES
ob_start();
$start_time = microtime(true);
$execution_error = '';

try {
    // Capturar errores fatales
    register_shutdown_function(function() use (&$execution_error) {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $execution_error = "Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'];
        }
    });
    
    include $snippet_file;
    
    $output = ob_get_clean();
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
    
    // Verificar si hubo error fatal
    if (!empty($execution_error)) {
        throw new Exception($execution_error);
    }
    
    error_log("Snippet executed successfully in " . $execution_time . "ms");
    
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
    
    // RESPUESTA JSON LIMPIA
    $response = [
        'success' => true,
        'html' => trim($html),
        'css' => trim($css),
        'js' => trim($js),
        'execution_time' => $execution_time,
        'file_used' => basename($snippet_file),
        'directory_used' => $snippets_dir,
        'organization' => 'simple',
        'timestamp' => time()
    ];
    
    // Limpiar output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    error_log("Returning successful response");
    echo json_encode($response);
    
} catch (ParseError $e) {
    ob_end_clean();
    $execution_error = "Parse Error: " . $e->getMessage() . " in line " . $e->getLine();
    error_log("Snippet parse error: " . $execution_error);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $execution_error,
        'file_used' => basename($snippet_file),
        'directory_used' => $snippets_dir,
        'error_type' => 'parse_error'
    ]);
    
} catch (Error $e) {
    ob_end_clean();
    $execution_error = "Fatal Error: " . $e->getMessage() . " in line " . $e->getLine();
    error_log("Snippet fatal error: " . $execution_error);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $execution_error,
        'file_used' => basename($snippet_file),
        'directory_used' => $snippets_dir,
        'error_type' => 'fatal_error'
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    $execution_error = "Exception: " . $e->getMessage();
    error_log("Snippet exception: " . $execution_error);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $execution_error,
        'file_used' => basename($snippet_file),
        'directory_used' => $snippets_dir,
        'error_type' => 'exception'
    ]);
}
?>
