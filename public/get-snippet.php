<?php
// get-snippet.php - Obtener código fuente de un snippet
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers CORS
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

// Función de logging
function debug_log($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] GET-SNIPPET: {$message}";
    if ($data !== null) {
        $log_entry .= " | Data: " . json_encode($data);
    }
    error_log($log_entry);
}

debug_log("=== NUEVA SOLICITUD DE OBTENCIÓN DE SNIPPET ===");

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

// Leer input
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
    'shortcode' => $input['shortcode'] ?? 'missing'
]);

// Validar campo requerido
if (!isset($input['shortcode'])) {
    debug_log("ERROR: Missing shortcode field", [
        'available_fields' => array_keys($input)
    ]);
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required field: shortcode',
        'received_fields' => array_keys($input),
        'required_fields' => ['shortcode']
    ]);
    exit;
}

// Procesar shortcode
$shortcode = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['shortcode']);

debug_log("Processing request", [
    'shortcode' => $shortcode
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

// Configurar directorio
$snippets_dir = __DIR__ . '/snippets/';
debug_log("Target directory", ['path' => $snippets_dir]);

// Verificar que el directorio exista
if (!is_dir($snippets_dir)) {
    debug_log("ERROR: Snippets directory does not exist", [
        'path' => $snippets_dir
    ]);
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Snippets directory not found',
        'directory' => $snippets_dir
    ]);
    exit;
}

// ESTRATEGIA 1: Buscar archivo EXACTO (shortcode.php)
$exact_file = $snippets_dir . $shortcode . '.php';
debug_log("Looking for exact file", ['path' => $exact_file]);

if (file_exists($exact_file)) {
    // Encontrado archivo exacto
    $code = file_get_contents($exact_file);

    if ($code === false) {
        debug_log("ERROR: Failed to read exact file", ['path' => $exact_file]);
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to read snippet file',
            'file' => $shortcode . '.php'
        ]);
        exit;
    }

    // Codificar en base64 (igual que save-snippet.php)
    $encoded_code = base64_encode($code);
    $file_size = filesize($exact_file);

    debug_log("Exact file found and read", [
        'filename' => $shortcode . '.php',
        'code_length' => strlen($code),
        'encoded_length' => strlen($encoded_code),
        'file_size' => $file_size
    ]);

    echo json_encode([
        'success' => true,
        'shortcode' => $shortcode,
        'code' => $encoded_code,
        'filename' => $shortcode . '.php',
        'file_size' => $file_size,
        'matched_by' => 'exact_match',
        'debug_info' => [
            'original_length' => strlen($code),
            'encoded_length' => strlen($encoded_code),
            'encoding' => 'base64'
        ]
    ]);
    exit;
}

// ESTRATEGIA 2: Buscar archivo con patrón shortcode_*.php (más reciente)
debug_log("Exact file not found, searching for pattern match");

$pattern = $snippets_dir . $shortcode . '_*.php';
$matching_files = glob($pattern);

debug_log("Pattern search", [
    'pattern' => $pattern,
    'matches_found' => count($matching_files)
]);

if (empty($matching_files)) {
    debug_log("ERROR: No matching files found", [
        'shortcode' => $shortcode,
        'pattern' => $pattern
    ]);
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Snippet not found',
        'shortcode' => $shortcode,
        'searched_patterns' => [
            $shortcode . '.php',
            $shortcode . '_*.php'
        ]
    ]);
    exit;
}

// Encontrar el archivo más reciente
$latest_file = null;
$latest_mtime = 0;

foreach ($matching_files as $file) {
    $mtime = filemtime($file);
    if ($mtime > $latest_mtime) {
        $latest_mtime = $mtime;
        $latest_file = $file;
    }
}

debug_log("Latest file selected", [
    'file' => basename($latest_file),
    'mtime' => date('Y-m-d H:i:s', $latest_mtime)
]);

// Leer el archivo más reciente
$code = file_get_contents($latest_file);

if ($code === false) {
    debug_log("ERROR: Failed to read latest file", ['path' => $latest_file]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to read snippet file',
        'file' => basename($latest_file)
    ]);
    exit;
}

// Codificar en base64
$encoded_code = base64_encode($code);
$file_size = filesize($latest_file);

debug_log("Pattern-matched file read successfully", [
    'filename' => basename($latest_file),
    'code_length' => strlen($code),
    'encoded_length' => strlen($encoded_code),
    'file_size' => $file_size
]);

echo json_encode([
    'success' => true,
    'shortcode' => $shortcode,
    'code' => $encoded_code,
    'filename' => basename($latest_file),
    'file_size' => $file_size,
    'matched_by' => 'pattern_match',
    'modified_at' => date('Y-m-d H:i:s', $latest_mtime),
    'debug_info' => [
        'original_length' => strlen($code),
        'encoded_length' => strlen($encoded_code),
        'encoding' => 'base64',
        'total_matches' => count($matching_files)
    ]
]);
?>
