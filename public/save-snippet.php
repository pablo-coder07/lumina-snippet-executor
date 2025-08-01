<?php
// save-snippet.php mejorado para Render
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

error_log("Save snippet request received at " . date('Y-m-d H:i:s'));

// Obtener datos del request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['shortcode']) || !isset($input['code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: shortcode and code']);
    exit;
}

// Limpiar nombre del shortcode
$shortcode = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['shortcode']);
$code = base64_decode($input['code']);

if (empty($code)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid base64 code']);
    exit;
}

// Crear directorio snippets si no existe
$snippets_dir = __DIR__ . '/snippets/';
if (!is_dir($snippets_dir)) {
    if (!mkdir($snippets_dir, 0755, true)) {
        error_log("Failed to create snippets directory: " . $snippets_dir);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create snippets directory']);
        exit;
    }
}

// Verificar permisos de escritura
if (!is_writable($snippets_dir)) {
    error_log("Snippets directory is not writable: " . $snippets_dir);
    http_response_code(500);
    echo json_encode(['error' => 'Snippets directory is not writable']);
    exit;
}

$timestamp = time();
$filename = $shortcode . '_' . $timestamp . '.php';
$filepath = $snippets_dir . $filename;

// Asegurar que el código comience con <?php
if (!str_starts_with(trim($code), '<?php')) {
    $code = "<?php\n" . $code;
}

// Intentar guardar el archivo
if (file_put_contents($filepath, $code)) {
    // Verificar que el archivo se guardó correctamente
    if (file_exists($filepath) && filesize($filepath) > 0) {
        error_log("Snippet saved successfully: " . $filename . " (size: " . filesize($filepath) . " bytes)");
        
        // Obtener estadísticas del directorio
        $total_files = count(glob($snippets_dir . '*.php'));
        
        echo json_encode([
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'shortcode' => $shortcode,
            'timestamp' => $timestamp,
            'size' => strlen($code),
            'file_size' => filesize($filepath),
            'total_snippets' => $total_files,
            'saved_at' => date('Y-m-d H:i:s', $timestamp),
            'directory' => $snippets_dir
        ]);
    } else {
        error_log("File was created but verification failed: " . $filepath);
        http_response_code(500);
        echo json_encode(['error' => 'File creation verification failed']);
    }
} else {
    error_log("Failed to save snippet: " . $filepath);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
}
?>
