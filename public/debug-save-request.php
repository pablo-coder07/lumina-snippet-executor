<?php
// debug-save-request.php - REEMPLAZAR temporalmente save-snippet.php para debug
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key, X-Request-ID');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// LOG COMPLETO DE LO QUE RECIBE RENDER
error_log("=== DEBUG SAVE REQUEST ===");
error_log("Timestamp: " . date('Y-m-d H:i:s'));
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'No especificado'));

// Headers recibidos
$headers = getallheaders();
error_log("Headers recibidos:");
foreach ($headers as $name => $value) {
    error_log("  " . $name . ": " . $value);
}

// Body raw
$raw_input = file_get_contents('php://input');
error_log("Raw input length: " . strlen($raw_input));
error_log("Raw input (primeros 500 chars): " . substr($raw_input, 0, 500));

// Intentar decodificar JSON
$input = json_decode($raw_input, true);
$json_error = json_last_error();

error_log("JSON decode result: " . ($json_error === JSON_ERROR_NONE ? 'SUCCESS' : 'FAILED'));
if ($json_error !== JSON_ERROR_NONE) {
    error_log("JSON error: " . json_last_error_msg());
}

// Verificar API key
$api_key = $_SERVER['HTTP_X_API_KEY'] ?? $headers['X-API-Key'] ?? '';
error_log("API Key recibida: " . (empty($api_key) ? 'VACÍA' : 'PRESENTE (' . strlen($api_key) . ' chars)'));

// Analizar campos del input
if ($input) {
    error_log("Campos recibidos en JSON:");
    foreach ($input as $key => $value) {
        if ($key === 'code') {
            error_log("  " . $key . ": [base64 data, " . strlen($value) . " chars]");
            
            // Verificar si el base64 es válido
            $decoded = base64_decode($value, true);
            if ($decoded !== false) {
                error_log("    - Base64 válido: " . strlen($decoded) . " bytes decodificados");
                error_log("    - Preview decodificado: " . substr($decoded, 0, 100) . "...");
            } else {
                error_log("    - Base64 INVÁLIDO");
            }
        } else {
            error_log("  " . $key . ": " . $value);
        }
    }
    
    // Verificar campos requeridos específicamente
    $required_fields = ['shortcode', 'code'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        error_log("❌ CAMPOS FALTANTES: " . implode(', ', $missing_fields));
    } else {
        error_log("✅ Todos los campos requeridos están presentes");
    }
} else {
    error_log("❌ No se pudo decodificar JSON o está vacío");
}

// POST variables (por si acaso)
if (!empty($_POST)) {
    error_log("POST variables:");
    foreach ($_POST as $key => $value) {
        error_log("  " . $key . ": " . $value);
    }
}

// GET variables
if (!empty($_GET)) {
    error_log("GET variables:");
    foreach ($_GET as $key => $value) {
        error_log("  " . $key . ": " . $value);
    }
}

error_log("=== FIN DEBUG ===");

// Respuesta para indicar que el debug funcionó
$response = [
    'debug_mode' => true,
    'timestamp' => time(),
    'method' => $_SERVER['REQUEST_METHOD'],
    'api_key_present' => !empty($api_key),
    'json_valid' => $json_error === JSON_ERROR_NONE,
    'raw_input_length' => strlen($raw_input),
    'fields_received' => $input ? array_keys($input) : [],
    'missing_fields' => $missing_fields ?? [],
    'message' => 'Debug completado - revisar logs del servidor'
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
