<?php
// save-snippet.php mejorado - Auto-crear directorio snippets
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
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

// Obtener datos del request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['shortcode']) || !isset($input['code'])) {
    error_log("ERROR: Missing required fields");
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: shortcode and code']);
    exit;
}

// Limpiar nombre del shortcode
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

// ASEGURAR QUE EL DIRECTORIO SNIPPETS EXISTE
$snippets_dir = __DIR__ . '/snippets/';
error_log("Snippets directory path: " . $snippets_dir);

// Verificar si existe
if (!is_dir($snippets_dir)) {
    error_log("Snippets directory does not exist, attempting to create...");
    
    // Intentar crear el directorio
    $mkdir_success = mkdir($snippets_dir, 0777, true);
    
    if ($mkdir_success) {
        error_log("✅ Snippets directory created successfully");
        
        // Aplicar permisos adicionales
        chmod($snippets_dir, 0777);
        error_log("Permissions applied to snippets directory");
    } else {
        error_log("❌ Failed to create snippets directory");
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to create snippets directory',
            'directory' => $snippets_dir
        ]);
        exit;
    }
} else {
    error_log("✅ Snippets directory already exists");
}

// Verificar permisos de escritura
if (!is_writable($snippets_dir)) {
    error_log("⚠️ Snippets directory is not writable, attempting to fix permissions...");
    
    // Intentar corregir permisos
    $chmod_success = chmod($snippets_dir, 0777);
    
    if (!$chmod_success || !is_writable($snippets_dir)) {
        error_log("❌ Cannot write to snippets directory");
        http_response_code(500);
        echo json_encode([
            'error' => 'Snippets directory is not writable',
            'directory' => $snippets_dir,
            'permissions' => substr(sprintf('%o', fileperms($snippets_dir)), -4)
        ]);
        exit;
    } else {
        error_log("✅ Permissions fixed, directory is now writable");
    }
}

// Generar nombre de archivo
$timestamp = time();
$filename = $shortcode . '_' . $timestamp . '.php';
$filepath = $snippets_dir . $filename;

error_log("Target filepath: " . $filepath);

// Asegurar que el código comience con <?php
if (!str_starts_with(trim($code), '<?php')) {
    $code = "<?php\n" . $code;
    error_log("Added <?php tag to code");
}

// Intentar guardar el archivo
$write_result = file_put_contents($filepath, $code);

if ($write_result === false) {
    error_log("❌ Failed to write file: " . $filepath);
    
    // Diagnóstico adicional
    $diagnostics = [
        'directory_exists' => is_dir($snippets_dir),
        'directory_writable' => is_writable($snippets_dir),
        'directory_permissions' => substr(sprintf('%o', fileperms($snippets_dir)), -4),
        'parent_directory' => dirname($snippets_dir),
        'parent_writable' => is_writable(dirname($snippets_dir)),
        'disk_space' => disk_free_space($snippets_dir)
    ];
    
    error_log("Write failure diagnostics: " . json_encode($diagnostics));
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to save file',
        'filepath' => $filepath,
        'diagnostics' => $diagnostics
    ]);
    exit;
}

// Verificar que el archivo se guardó correctamente
if (!file_exists($filepath)) {
    error_log("❌ File was not created: " . $filepath);
    http_response_code(500);
    echo json_encode(['error' => 'File creation verification failed']);
    exit;
}

$file_size = filesize($filepath);
if ($file_size === false || $file_size === 0) {
    error_log("❌ File is empty or unreadable: " . $filepath);
    http_response_code(500);
    echo json_encode(['error' => 'File is empty after creation']);
    exit;
}

// Obtener estadísticas finales
$total_files = count(glob($snippets_dir . '*.php'));

error_log("✅ File saved successfully: " . $filename);
error_log("File size: " . $file_size . " bytes");
error_log("Total PHP files in directory: " . $total_files);

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
    'directory' => $snippets_dir,
    'directory_permissions' => substr(sprintf('%o', fileperms($snippets_dir)), -4)
]);
?>
