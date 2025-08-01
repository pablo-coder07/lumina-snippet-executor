<?php
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
$shortcode_name = $input['shortcode'] ?? '';

if (empty($shortcode_name)) {
    http_response_code(400);
    echo json_encode(['error' => 'Shortcode name required']);
    exit;
}

error_log("Looking for shortcode: " . $shortcode_name);

// USAR MÚLTIPLES DIRECTORIOS POSIBLES
$possible_directories = [
    __DIR__ . '/code_snippets/',  // Directorio alternativo que funciona
    __DIR__ . '/snippets/',       // Directorio original (por si se arregla)
    __DIR__ . '/php_files/',      // Otro alternativo
    __DIR__ . '/'                 // Directorio actual como último recurso
];

$snippet_file = null;
$latest_timestamp = 0;
$directory_used = null;

foreach ($possible_directories as $snippet_dir) {
    if (!is_dir($snippet_dir)) {
        continue;
    }
    
    error_log("Searching in directory: " . $snippet_dir);
    
    $files = @scandir($snippet_dir);
    if (!$files) {
        continue;
    }
    
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            // Buscar archivos que coincidan con el shortcode
            if (preg_match('/^' . preg_quote($shortcode_name, '/') . '(?:_v\d+)?_(\d+)\.php$/', $file, $matches)) {
                $file_timestamp = intval($matches[1]);
                if ($file_timestamp > $latest_timestamp) {
                    $latest_timestamp = $file_timestamp;
                    $snippet_file = $snippet_dir . $file;
                    $directory_used = $snippet_dir;
                }
                error_log("Found candidate file: " . $file . " (timestamp: " . $file_timestamp . ")");
            }
        }
    }
    
    // Si encontramos archivo en este directorio, no buscar en otros
    if ($snippet_file) {
        break;
    }
}

if (!$snippet_file || !file_exists($snippet_file)) {
    error_log("Snippet file not found for: " . $shortcode_name);
    
    // Información de debugging
    $debug_info = [];
    foreach ($possible_directories as $dir) {
        if (is_dir($dir)) {
            $files = @scandir($dir);
            $php_files = array_filter($files ?: [], function($f) { 
                return pathinfo($f, PATHINFO_EXTENSION) === 'php'; 
            });
            $debug_info[] = [
                'directory' => $dir,
                'exists' => true,
                'php_files' => array_values($php_files)
            ];
        } else {
            $debug_info[] = [
                'directory' => $dir,
                'exists' => false
            ];
        }
    }
    
    http_response_code(404);
    echo json_encode([
        'error' => 'Snippet not found',
        'shortcode' => $shortcode_name,
        'directories_searched' => $debug_info
    ]);
    exit;
}

error_log("Executing snippet file: " . basename($snippet_file));
error_log("From directory: " . $directory_used);

ob_start();
$start_time = microtime(true);

try {
    include $snippet_file;
    $output = ob_get_clean();
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);

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

    echo json_encode([
        'success' => true,
        'html' => trim($html),
        'css' => trim($css),
        'js' => trim($js),
        'execution_time' => $execution_time,
        'file_used' => basename($snippet_file),
        'directory_used' => $directory_used,
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    ob_end_clean();
    error_log("Snippet execution error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Execution error: ' . $e->getMessage(),
        'file_used' => basename($snippet_file),
        'directory_used' => $directory_used
    ]);
}
?>
