<?php
// execute-snippet.php MODIFICADO - Buscar en carpetas por usuario
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

error_log("Execute snippet request (organized) received at " . date('Y-m-d H:i:s'));

$input = json_decode(file_get_contents('php://input'), true);
$shortcode_name = $input['shortcode'] ?? '';
$user_id = $input['user_id'] ?? 1; // ID del usuario desde WordPress

if (empty($shortcode_name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Shortcode name required']);
    exit;
}

error_log("Looking for shortcode: " . $shortcode_name . " (User: " . $user_id . ")");

// BUSCAR EN CARPETAS ORGANIZADAS POR USUARIO
$base_snippets_dir = __DIR__ . '/snippets/';
$user_folder = 'usuario_' . $user_id;
$user_dir = $base_snippets_dir . $user_folder . '/';

error_log("Searching in user directory: " . $user_dir);

// Verificar que el directorio base existe
if (!is_dir($base_snippets_dir)) {
    error_log("CRITICAL: Base snippets directory does not exist: " . $base_snippets_dir);
    http_response_code(500);
    echo json_encode([
        'error' => 'Base snippets directory not found',
        'directory' => $base_snippets_dir
    ]);
    exit;
}

// Verificar que el directorio del usuario existe
if (!is_dir($user_dir)) {
    error_log("User directory does not exist: " . $user_dir);
    
    // Buscar en todas las carpetas de usuarios como fallback
    error_log("Searching in all user directories...");
    $all_user_dirs = glob($base_snippets_dir . 'usuario_*', GLOB_ONLYDIR);
    
    if (empty($all_user_dirs)) {
        http_response_code(404);
        echo json_encode([
            'error' => 'No user directories found',
            'base_directory' => $base_snippets_dir,
            'expected_user_dir' => $user_dir,
            'user_id' => $user_id
        ]);
        exit;
    }
    
    // Buscar el shortcode en todas las carpetas de usuarios
    $found_in_dirs = [];
    foreach ($all_user_dirs as $dir) {
        $files = glob($dir . '/*.php');
        foreach ($files as $file) {
            $filename = basename($file, '.php');
            if (strpos($filename, $shortcode_name) === 0) {
                $found_in_dirs[] = [
                    'directory' => basename($dir),
                    'file' => basename($file),
                    'full_path' => $file
                ];
            }
        }
    }
    
    if (!empty($found_in_dirs)) {
        // Usar el primer archivo encontrado
        $snippet_file = $found_in_dirs[0]['full_path'];
        $used_directory = $found_in_dirs[0]['directory'];
        error_log("Found shortcode in different user directory: " . $used_directory);
    } else {
        http_response_code(404);
        echo json_encode([
            'error' => 'Shortcode not found in any user directory',
            'shortcode' => $shortcode_name,
            'searched_directories' => array_map('basename', $all_user_dirs),
            'user_id' => $user_id
        ]);
        exit;
    }
} else {
    // Buscar en el directorio específico del usuario
    $snippet_file = null;
    $latest_timestamp = 0;
    
    $files = @scandir($user_dir);
    if (!$files) {
        error_log("Cannot read user directory: " . $user_dir);
        http_response_code(500);
        echo json_encode(['error' => 'Cannot read user directory']);
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
                        $snippet_file = $user_dir . $file;
                    }
                    error_log("Found candidate file: " . $file . " (timestamp: " . $file_timestamp . ")");
                    break;
                }
            }
        }
    }
    
    $used_directory = $user_folder;
}

if (!$snippet_file || !file_exists($snippet_file)) {
    error_log("Snippet file not found for: " . $shortcode_name);
    
    // Listar archivos disponibles para debug
    $available_files = [];
    if (is_dir($user_dir)) {
        $files = glob($user_dir . '*.php');
        $available_files = array_map('basename', $files);
    }
    
    http_response_code(404);
    echo json_encode([
        'error' => 'Snippet not found',
        'shortcode' => $shortcode_name,
        'user_id' => $user_id,
        'searched_in' => $user_dir,
        'available_files' => $available_files
    ]);
    exit;
}

error_log("Executing snippet file: " . basename($snippet_file));
error_log("From directory: " . $used_directory);

// EJECUCIÓN (igual que antes pero con info de organización)
ob_start();
$start_time = microtime(true);
$execution_error = '';

try {
    register_shutdown_function(function() use (&$execution_error) {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $execution_error = "Fatal Error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'];
        }
    });
    
    include $snippet_file;
    
    $output = ob_get_clean();
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
    
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
    
    // RESPUESTA CON INFORMACIÓN DE ORGANIZACIÓN
    $response = [
        'success' => true,
        'html' => trim($html),
        'css' => trim($css),
        'js' => trim($js),
        'execution_time' => $execution_time,
        'file_used' => basename($snippet_file),
        'user_directory' => $used_directory,
        'user_id' => $user_id,
        'organization' => 'user_folders',
        'timestamp' => time()
    ];
    
    while (ob_get_level()) {
        ob_end_clean();
    }
    
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
        'user_directory' => $used_directory,
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
        'user_directory' => $used_directory,
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
        'user_directory' => $used_directory,
        'error_type' => 'exception'
    ]);
}
?>
