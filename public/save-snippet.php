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

$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($api_key !== 'lumina-secure-key-2024') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

error_log("Save snippet request received at " . date('Y-m-d H:i:s'));

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['shortcode']) || !isset($input['code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: shortcode and code']);
    exit;
}

$shortcode = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['shortcode']);
$code = base64_decode($input['code']);

if (empty($code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid base64 code']);
    exit;
}

$snippets_dir = __DIR__ . '/snippets/';
if (!is_dir($snippets_dir)) {
    mkdir($snippets_dir, 0755, true);
}

$timestamp = time();
$filename = $snippets_dir . $shortcode . '_' . $timestamp . '.php';

if (!str_starts_with(trim($code), '<?php')) {
    $code = "<?php\n" . $code;
}

if (file_put_contents($filename, $code)) {
    error_log("Snippet saved successfully: " . basename($filename));
    
    echo json_encode([
        'success' => true,
        'filename' => basename($filename),
        'shortcode' => $shortcode,
        'timestamp' => $timestamp,
        'size' => strlen($code)
    ]);
} else {
    error_log("Failed to save snippet: " . $filename);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
}
?>
