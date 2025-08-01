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

// Verificar API key
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($api_key !== 'lumina-secure-key-2024') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

error_log("=== SAVE SNIPPET REQUEST ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));

// Obtener datos del request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['shortcode']) || !isset($input['code'])) {
    error_log("ERROR: Missing required fields");
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: shortcode and code']);
    exit;
}

$shortcode = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['shortcode']);
$code = base64_decode($input['code']);

if (empty($code)) {
    error_log("ERROR: Invalid base64 code");
    http_response_code(400);
    echo json_encode(['error' => 'Invalid base64 code']);
    exit;
}

error_log("Processing shortcode: " . $shortcode);
error_log("Code length: " . strlen($code) . " bytes");

// USAR SOLO EL DIRECTORIO SNIPPETS
$snippets_dir = __DIR__ . '/snippets/';

error_log("Target directory: " . $snippets_dir);

// Verificar que el directorio exists y es escribible
if (!is_dir($snippets_dir)) {
    error_log("ERROR: Snippets directory does not exist");
    http_response_code(500);
    echo json_encode([
        'error' => 'Snippets directory not found',
        'directory' => $snippets_dir,
        'suggestion' => 'Run fix-snippets-directory.php first'
    ]);
    exit;
}

if (!is_writable($snippets_dir)) {
    error_log("ERROR: Snippets directory is not writable");
    http_response_code(500);
    echo json_encode([
        'error' => 'Snippets directory is not writable',
        'directory' => $snippets_dir,
        'permissions' => substr(sprintf('%o', fileperms($snippets_dir)), -4)
    ]);
    exit;
}

// Generar nombre de archivo
$timestamp = time();
$filename = $shortcode . '_' . $timestamp . '.php';
$filepath = $snippets_dir . $filename;

error_log("Target filepath: " . $filepath);

// Asegurar que el código comience con <?php
if (!str_starts_with(trim($code), '<?php')) {
    $code = "<?php\n" . $code;
}

// Guardar el archivo
$write_result = file_put_contents($filepath, $code);

if ($write_result === false) {
    error_log("ERROR: Failed to write file: " . $filepath);
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to save file',
        'filepath' => $filepath,
        'directory' => $snippets_dir,
        'directory_writable' => is_writable($snippets_dir)
    ]);
    exit;
}

// Verificar que el archivo se guardó
if (!file_exists($filepath)) {
    error_log("ERROR: File was not created: " . $filepath);
    http_response_code(500);
    echo json_encode(['error' => 'File creation verification failed']);
    exit;
}

$file_size = filesize($filepath);
$total_files = count(glob($snippets_dir . '*.php'));

error_log("SUCCESS: File saved successfully");
error_log("Filename: " . $filename);
error_log("File size: " . $file_size . " bytes");
error_log("Total PHP files: " . $total_files);

// Respuesta exitosa
echo json_encode([
    'success' => true,
    'filename' => $filename,
    'filepath' => $filepath,
    'shortcode' => $shortcode,
    'timestamp' => $timestamp,
    'size' => strlen($code),
    'file_size' => $file_size,
    'total_snippets' => $total_files,
    'saved_at' => date('Y-m-d H:i:s', $timestamp),
    'working_directory' => $snippets_dir
]);
?>
