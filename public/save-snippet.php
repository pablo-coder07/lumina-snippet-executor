<?php
// save-snippet.php - Estrategias múltiples para crear directorio  
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

// ESTRATEGIA MÚLTIPLE PARA ENCONTRAR/CREAR DIRECTORIO DE TRABAJO
$directory_strategies = [
    'snippets' => __DIR__ . '/snippets/',
    'code_snippets' => __DIR__ . '/code_snippets/',
    'php_files' => __DIR__ . '/php_files/',
    'current_dir' => __DIR__ . '/'
];

$working_directory = null;
$strategy_used = null;
$creation_log = [];

foreach ($directory_strategies as $strategy_name => $dir_path) {
    error_log("Trying strategy: " . $strategy_name . " -> " . $dir_path);
    
    $strategy_result = [
        'strategy' => $strategy_name,
        'path' => $dir_path,
        'initially_exists' => is_dir($dir_path),
        'initially_writable' => is_writable($dir_path)
    ];
    
    // Si ya existe y es escribible, usarlo
    if (is_dir($dir_path) && is_writable($dir_path)) {
        $working_directory = $dir_path;
        $strategy_used = $strategy_name;
        $strategy_result['result'] = 'used_existing';
        $creation_log[] = $strategy_result;
        error_log("✅ Using existing directory: " . $dir_path);
        break;
    }
    
    // Si no existe, intentar crearlo (excepto current_dir que siempre existe)
    if (!is_dir($dir_path) && $strategy_name !== 'current_dir') {
        // Remover cualquier archivo que pueda estar bloqueando
        if (is_file($dir_path)) {
            $unlink_result = @unlink($dir_path);
            $strategy_result['removed_file'] = $unlink_result;
            error_log("Removed blocking file: " . ($unlink_result ? 'SUCCESS' : 'FAILED'));
        }
        
        // Intentar crear directorio
        $mkdir_result = @mkdir($dir_path, 0777, true);
        $strategy_result['mkdir_attempted'] = true;
        $strategy_result['mkdir_success'] = $mkdir_result;
        
        if ($mkdir_result) {
            // Aplicar permisos
            @chmod($dir_path, 0777);
            $strategy_result['chmod_applied'] = true;
            
            // Verificar que quedó escribible
            if (is_writable($dir_path)) {
                $working_directory = $dir_path;
                $strategy_used = $strategy_name;
                $strategy_result['result'] = 'created_and_used';
                $creation_log[] = $strategy_result;
                error_log("✅ Created and using directory: " . $dir_path);
                break;
            } else {
                $strategy_result['result'] = 'created_but_not_writable';
                error_log("⚠️ Created but not writable: " . $dir_path);
            }
        } else {
            $strategy_result['result'] = 'creation_failed';
            error_log("❌ Failed to create: " . $dir_path);
        }
    }
    
    // Para current_dir, solo verificar si es escribible
    if ($strategy_name === 'current_dir' && is_writable($dir_path)) {
        $working_directory = $dir_path;
        $strategy_used = $strategy_name;
        $strategy_result['result'] = 'used_current_dir';
        $creation_log[] = $strategy_result;
        error_log("✅ Using current directory as fallback: " . $dir_path);
        break;
    }
    
    $creation_log[] = $strategy_result;
}

// Verificar que tenemos un directorio de trabajo
if (!$working_directory) {
    error_log("❌ No working directory found");
    http_response_code(500);
    echo json_encode([
        'error' => 'Cannot find or create a writable directory',
        'strategies_attempted' => $creation_log,
        'diagnostics' => [
            'current_dir_writable' => is_writable(__DIR__),
            'current_dir_permissions' => substr(sprintf('%o', fileperms(__DIR__)), -4),
            'user' => get_current_user(),
            'uid' => getmyuid(),
            'gid' => getmygid()
        ]
    ]);
    exit;
}

error_log("Using working directory: " . $working_directory . " (strategy: " . $strategy_used . ")");

// Generar nombre de archivo
$timestamp = time();
$filename = $shortcode . '_' . $timestamp . '.php';
$filepath = $working_directory . $filename;

error_log("Target filepath: " . $filepath);

// Asegurar que el código comience con <?php
if (!str_starts_with(trim($code), '<?php')) {
    $code = "<?php\n" . $code;
}

// Intentar guardar el archivo
$write_result = file_put_contents($filepath, $code);

if ($write_result === false) {
    error_log("❌ Failed to write file: " . $filepath);
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to save file',
        'filepath' => $filepath,
        'working_directory' => $working_directory,
        'strategy_used' => $strategy_used,
        'directory_strategies' => $creation_log
    ]);
    exit;
}

// Verificar que el archivo se guardó
if (!file_exists($filepath)) {
    error_log("❌ File was not created: " . $filepath);
    http_response_code(500);
    echo json_encode(['error' => 'File creation verification failed']);
    exit;
}

$file_size = filesize($filepath);
$total_files = count(glob($working_directory . '*.php'));

error_log("✅ File saved successfully: " . $filename);
error_log("Working directory: " . $working_directory . " (strategy: " . $strategy_used . ")");
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
    'working_directory' => $working_directory,
    'strategy_used' => $strategy_used,
    'directory_creation_log' => $creation_log
]);
?>
