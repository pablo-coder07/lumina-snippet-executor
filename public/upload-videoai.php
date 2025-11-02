<?php
// Endpoint TEMPORAL para subir videoai_generator con Firestore
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Verificar API key
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($api_key !== 'lumina-secure-key-2024') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (!isset($input['code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing code field']);
    exit;
}

$code = base64_decode($input['code']);
if ($code === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid base64']);
    exit;
}

// Guardar directamente
$snippets_dir = __DIR__ . '/snippets/';
if (!is_dir($snippets_dir)) {
    mkdir($snippets_dir, 0755, true);
}

$filename = 'videoai_generator_v1_dlzdbdydjgjoapbb.php';
$filepath = $snippets_dir . $filename;

$result = file_put_contents($filepath, $code, LOCK_EX);

if ($result === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to write file']);
    exit;
}

echo json_encode([
    'success' => true,
    'filename' => $filename,
    'size' => $result,
    'path' => $filepath
]);
?>
