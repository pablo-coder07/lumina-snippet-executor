<?php
// save-snippet.php MODIFICADO - Guardar en carpetas por usuario
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

error_log("=== SAVE SNIPPET REQUEST (ORGANIZED) ===");
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
$user_id = $input['user_id'] ?? 1; // ID del usuario desde WordPress

if (empty($code)) {
    error_log("ERROR: Invalid base64 code");
    http_response_code(400);
    echo json_encode(['error' => 'Invalid base64 code']);
    exit;
}

error_log("Processing shortcode: " . $shortcode);
error_log("Code length: " . strlen($code) . " bytes");
error_log("User ID: " . $user_id);

// CREAR ESTRUCTURA DE CARPETAS POR USUARIO
$base_snippets_dir = __DIR__ . '/snippets/';
$user_folder = 'usuario_' . $user_id;
$user_dir = $base_snippets_dir . $user_folder . '/';

error_log("User directory: " . $user_dir);

// Crear directorio base snippets si no existe
if (!is_dir($base_snippets_dir)) {
    error_log("Creating base snippets directory");
    $base_result = mkdir($base_snippets_dir, 0777, true);
    if (!$base_result) {
        error_log("ERROR: Could not create base snippets directory");
        http_response_code(500);
        echo json_encode([
            'error' => 'Could not create base snippets directory',
            'directory' => $base_snippets_dir
        ]);
        exit;
    }
}

// Crear directorio del usuario si no existe
if (!is_dir($user_dir)) {
    error_log("Creating user directory: " . $user_folder);
    $user_result = mkdir($user_dir, 0777, true);
    if (!$user_result) {
        error_log("ERROR: Could not create user directory");
        http_response_code(500);
        echo json_encode([
            'error' => 'Could not create user directory',
            'directory' => $user_dir,
            'user_folder' => $user_folder
        ]);
        exit;
    }
    
    // Crear archivo README en la carpeta del usuario
    $readme_content = "# Carpeta de códigos para Usuario {$user_id}\n\n";
    $readme_content .= "Fecha de creación: " . date('Y-m-d H:i:s') . "\n";
    $readme_content .= "Todos los códigos PHP generados por Claude para este usuario se guardan aquí.\n";
    
    file_put_contents($user_dir . 'README.md', $readme_content);
    error_log("Created README.md for user folder");
}

// Verificar que el directorio del usuario sea escribible
if (!is_writable($user_dir)) {
    error_log("ERROR: User directory is not writable");
    http_response_code(500);
    echo json_encode([
        'error' => 'User directory is not writable',
        'directory' => $user_dir,
        'permissions' => substr(sprintf('%o', fileperms($user_dir)), -4)
    ]);
    exit;
}

// Generar nombre de archivo dentro de la carpeta del usuario
$timestamp = time();
$filename = $shortcode . '_' . $timestamp . '.php';
$filepath = $user_dir . $filename;

error_log("Target filepath: " . $filepath);

// Asegurar que el código comience con <?php
if (!str_starts_with(trim($code), '<?php')) {
    $code = "<?php\n" . $code;
}

// Agregar metadatos como comentario al inicio del archivo
$metadata_comment = "<?php\n";
$metadata_comment .= "/*\n";
$metadata_comment .= " * Código generado por DrawCode AI\n";
$metadata_comment .= " * Usuario: {$user_id}\n";
$metadata_comment .= " * Shortcode: [{$shortcode}]\n";
$metadata_comment .= " * Fecha: " . date('Y-m-d H:i:s') . "\n";
$metadata_comment .= " * Timestamp: {$timestamp}\n";
$metadata_comment .= " */\n\n";

// Remover el <?php del código original y agregar nuestros metadatos
$code = $metadata_comment . ltrim($code, "<?php \n");

// Guardar el archivo
$write_result = file_put_contents($filepath, $code);

if ($write_result === false) {
    error_log("ERROR: Failed to write file: " . $filepath);
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to save file',
        'filepath' => $filepath,
        'directory' => $user_dir,
        'user_folder' => $user_folder
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

// Contar archivos del usuario
$user_files = glob($user_dir . '*.php');
$total_user_files = count($user_files);

// Contar archivos totales en todas las carpetas
$all_user_dirs = glob($base_snippets_dir . 'usuario_*', GLOB_ONLYDIR);
$total_all_files = 0;
foreach ($all_user_dirs as $dir) {
    $total_all_files += count(glob($dir . '/*.php'));
}

$file_size = filesize($filepath);

error_log("SUCCESS: File saved successfully");
error_log("User folder: " . $user_folder);
error_log("Filename: " . $filename);
error_log("File size: " . $file_size . " bytes");
error_log("User files: " . $total_user_files);
error_log("Total files: " . $total_all_files);

// Respuesta exitosa con información organizacional
echo json_encode([
    'success' => true,
    'filename' => $filename,
    'filepath' => $filepath,
    'shortcode' => $shortcode,
    'timestamp' => $timestamp,
    'size' => strlen($code),
    'file_size' => $file_size,
    'user_id' => $user_id,
    'user_folder' => $user_folder,
    'user_files_count' => $total_user_files,
    'total_files_count' => $total_all_files,
    'saved_at' => date('Y-m-d H:i:s', $timestamp),
    'working_directory' => $user_dir,
    'organization' => 'user_folders'
]);
?>
