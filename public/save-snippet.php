<?php
// save-snippet.php MEJORADO - Con backup automático a GitHub
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers CORS mejorados
header('Content-Type: application/json; charset=utf-8');
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

// Función de logging mejorada
function debug_log($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] SAVE-SNIPPET: {$message}";
    if ($data !== null) {
        $log_entry .= " | Data: " . json_encode($data);
    }
    error_log($log_entry);
}

// Función para generar código aleatorio con letras minúsculas
function generate_random_code($length = 8) {
    $characters = 'abcdefghijklmnopqrstuvwxyz';
    $code = '';
    $max = strlen($characters) - 1;

    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, $max)];
    }

    return $code;
}

debug_log("=== NUEVA SOLICITUD DE GUARDADO ===");

// Verificar API key
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
debug_log("API Key received", ['key_length' => strlen($api_key), 'key_present' => !empty($api_key)]);

if ($api_key !== 'lumina-secure-key-2024') {
    debug_log("ERROR: Unauthorized - Invalid API key");
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized - Invalid or missing API key'
    ]);
    exit;
}

// Leer input con validación mejorada
$raw_input = file_get_contents('php://input');
debug_log("Input received", ['length' => strlen($raw_input)]);

if (empty($raw_input)) {
    debug_log("ERROR: Empty input");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Empty request body'
    ]);
    exit;
}

$input = json_decode($raw_input, true);
$json_error = json_last_error();

if ($json_error !== JSON_ERROR_NONE) {
    debug_log("ERROR: JSON decode failed", [
        'error' => json_last_error_msg(),
        'raw_preview' => substr($raw_input, 0, 200)
    ]);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON: ' . json_last_error_msg()
    ]);
    exit;
}

debug_log("Input decoded", [
    'fields' => array_keys($input),
    'shortcode' => $input['shortcode'] ?? 'missing',
    'code_length' => isset($input['code']) ? strlen($input['code']) : 0
]);

// Validar campos requeridos
if (!isset($input['shortcode']) || !isset($input['code'])) {
    debug_log("ERROR: Missing required fields", [
        'has_shortcode' => isset($input['shortcode']),
        'has_code' => isset($input['code']),
        'available_fields' => array_keys($input)
    ]);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: shortcode and code',
        'received_fields' => array_keys($input),
        'required_fields' => ['shortcode', 'code']
    ]);
    exit;
}

// Procesar datos
$shortcode = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['shortcode']);
$encoded_code = $input['code'];
$user_id = $input['user_id'] ?? 1;
$timestamp = $input['timestamp'] ?? time();

debug_log("Processing data", [
    'shortcode' => $shortcode,
    'encoded_code_length' => strlen($encoded_code),
    'user_id' => $user_id,
    'timestamp' => $timestamp
]);

// Validar shortcode
if (empty($shortcode)) {
    debug_log("ERROR: Invalid shortcode after sanitization");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid shortcode name',
        'original_shortcode' => $input['shortcode']
    ]);
    exit;
}

// Decodificar código con validación
$code = base64_decode($encoded_code);
if ($code === false || empty($code)) {
    debug_log("ERROR: Base64 decode failed", [
        'encoded_length' => strlen($encoded_code),
        'encoded_preview' => substr($encoded_code, 0, 100)
    ]);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid base64 encoded code'
    ]);
    exit;
}

debug_log("Code decoded successfully", [
    'decoded_length' => strlen($code),
    'starts_with_php' => strpos(trim($code), '<?php') === 0
]);

// Configurar directorio
$snippets_dir = __DIR__ . '/snippets/';
debug_log("Target directory", ['path' => $snippets_dir]);

// Verificar/crear directorio
if (!is_dir($snippets_dir)) {
    debug_log("Creating snippets directory");
    if (!mkdir($snippets_dir, 0755, true)) {
        debug_log("ERROR: Cannot create snippets directory", [
            'path' => $snippets_dir,
            'parent_writable' => is_writable(__DIR__)
        ]);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Cannot create snippets directory',
            'directory' => $snippets_dir
        ]);
        exit;
    }
}

// Verificar permisos
if (!is_writable($snippets_dir)) {
    debug_log("ERROR: Directory not writable", [
        'directory' => $snippets_dir,
        'permissions' => substr(sprintf('%o', fileperms($snippets_dir)), -4)
    ]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Snippets directory is not writable',
        'directory' => $snippets_dir,
        'permissions' => substr(sprintf('%o', fileperms($snippets_dir)), -4)
    ]);
    exit;
}

// 🆕 CAMBIO CLAVE: El shortcode YA incluye la versión y código aleatorio
// No agregar código adicional - usar el nombre exacto que viene de WordPress
// Formato esperado: base_v1_codigo (ej: hola_mundo_simple_v1_eztenncy)

debug_log("Using shortcode name as-is (no additional code)", [
    'shortcode' => $shortcode,
    'includes_version' => preg_match('/_v\d+_[a-z]{8}$/', $shortcode) ? 'YES' : 'NO'
]);

// El nombre del archivo es exactamente el shortcode + .php
$filename = $shortcode . '.php';
$filepath = $snippets_dir . $filename;

// Extraer código aleatorio del shortcode para los metadatos
$random_code = '';
if (preg_match('/_([a-z]{8})$/', $shortcode, $matches)) {
    $random_code = $matches[1];
} else {
    // Fallback si no tiene código (no debería pasar)
    $random_code = generate_random_code(8);
}

debug_log("Preparing file", [
    'filename' => $filename,
    'filepath' => $filepath
]);

// Asegurar formato PHP correcto
if (!str_starts_with(trim($code), '<?php')) {
    $code = "<?php\n" . $code;
    debug_log("Added PHP opening tag");
}

// Agregar metadatos
$actual_timestamp = is_numeric($timestamp) && $timestamp > 1000000000 ? $timestamp : time();
$metadata_comment = "<?php\n";
$metadata_comment .= "/*\n";
$metadata_comment .= " * Código generado por DrawCode AI\n";
$metadata_comment .= " * Shortcode: [{$shortcode}]\n";
$metadata_comment .= " * Fecha: " . date('Y-m-d H:i:s', $actual_timestamp) . "\n";
$metadata_comment .= " * ID Único: {$random_code}\n";
$metadata_comment .= " * User ID: {$user_id}\n";
$metadata_comment .= " */\n\n";

// Combinar metadatos con código
$final_code = $metadata_comment . ltrim($code, "<?php \n\r\t");

debug_log("Final code prepared", [
    'final_length' => strlen($final_code),
    'includes_metadata' => strpos($final_code, 'DrawCode AI') !== false
]);

// Guardar archivo con verificación
$write_result = file_put_contents($filepath, $final_code, LOCK_EX);

if ($write_result === false) {
    debug_log("ERROR: Failed to write file", [
        'filepath' => $filepath,
        'directory_writable' => is_writable($snippets_dir),
        'directory_exists' => is_dir($snippets_dir)
    ]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save file',
        'filepath' => $filepath
    ]);
    exit;
}

// Verificar que el archivo se creó correctamente
if (!file_exists($filepath)) {
    debug_log("ERROR: File creation verification failed");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'File creation verification failed',
        'filepath' => $filepath
    ]);
    exit;
}

$file_size = filesize($filepath);
$total_files = count(glob($snippets_dir . '*.php'));

debug_log("File saved successfully", [
    'filename' => $filename,
    'file_size' => $file_size,
    'bytes_written' => $write_result,
    'total_php_files' => $total_files
]);

// ================================================================
// NUEVO: BACKUP AUTOMÁTICO A GITHUB
// ================================================================

$backup_success = false;
$backup_error = null;

try {
    debug_log("🔄 INICIANDO BACKUP AUTOMÁTICO A GITHUB");
    
    // Incluir clase de backup
    require_once __DIR__ . '/github-backup.php';
    
    // Crear instancia de backup
    $github_backup = new GitHubBackup();
    
    // Realizar backup del archivo recién creado
    $backup_result = $github_backup->backup_file($filepath, $filename);
    
    if ($backup_result) {
        $backup_success = true;
        debug_log("✅ BACKUP AUTOMÁTICO EXITOSO", ['filename' => $filename]);
    } else {
        $backup_error = "Falló el backup automático";
        debug_log("❌ BACKUP AUTOMÁTICO FALLÓ", ['filename' => $filename]);
    }
    
} catch (Exception $e) {
    $backup_error = $e->getMessage();
    debug_log("❌ ERROR EN BACKUP AUTOMÁTICO", [
        'error' => $e->getMessage(),
        'filename' => $filename
    ]);
}

// Test de lectura inmediato para asegurar integridad
$read_test = file_get_contents($filepath);
$read_test_success = $read_test !== false && strlen($read_test) === $file_size;

debug_log("Read test", [
    'success' => $read_test_success,
    'read_length' => $read_test !== false ? strlen($read_test) : 0,
    'expected_length' => $file_size
]);

// Respuesta exitosa con información completa + info de backup
$response = [
    'success' => true,
    'filename' => $filename,
    'filepath' => $filepath,
    'shortcode' => $shortcode,
    'timestamp' => $timestamp,
    'size' => strlen($final_code),
    'file_size' => $file_size,
    'total_snippets' => $total_files,
    'saved_at' => date('Y-m-d H:i:s', $timestamp),
    'working_directory' => $snippets_dir,
    'read_test_passed' => $read_test_success,
    // NUEVA INFORMACIÓN DE BACKUP
    'github_backup' => [
        'enabled' => true,
        'success' => $backup_success,
        'error' => $backup_error,
        'timestamp' => date('Y-m-d H:i:s')
    ],
    'debug_info' => [
        'user_id' => $user_id,
        'original_code_length' => strlen($code),
        'final_code_length' => strlen($final_code),
        'includes_metadata' => true,
        'php_format_correct' => str_starts_with(trim($final_code), '<?php'),
        'backup_attempted' => true
    ]
];

debug_log("Returning success response", [
    'filename' => $filename,
    'size' => $file_size,
    'total_files' => $total_files,
    'backup_success' => $backup_success
]);

echo json_encode($response);
?>
