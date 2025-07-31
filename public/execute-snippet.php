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

$snippet_file = null;
$snippet_dir = __DIR__ . '/snippets/';
$latest_timestamp = 0;

if (is_dir($snippet_dir)) {
    $files = scandir($snippet_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            if (preg_match('/^' . preg_quote($shortcode_name, '/') . '(?:_v\d+)?_(\d+)\.php$/', $file, $matches)) {
                $file_timestamp = intval($matches[1]);
                if ($file_timestamp > $latest_timestamp) {
                    $latest_timestamp = $file_timestamp;
                    $snippet_file = $snippet_dir . $file;
                }
                error_log("Found candidate file: " . $file . " (timestamp: " . $file_timestamp . ")");
            }
        }
    }
}

if (!$snippet_file || !file_exists($snippet_file)) {
    error_log("Snippet file not found for: " . $shortcode_name);
    http_response_code(404);
    echo json_encode([
        'error' => 'Snippet not found',
        'shortcode' => $shortcode_name,
        'searched_in' => $snippet_dir
    ]);
    exit;
}

error_log("Executing snippet file: " . basename($snippet_file));

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
    
    if (preg_match_all('/<style[^>]*>(.*?)<\/style>/is', $output, $css_matches)) {
        $css = implode("\n", $css_matches[1]);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
    }
    
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
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    error_log("Snippet execution error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Execution error: ' . $e->getMessage(),
        'file_used' => basename($snippet_file)
    ]);
}
?>
