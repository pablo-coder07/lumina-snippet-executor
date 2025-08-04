<?php
// test-save-render.php - Subir a public/ para probar guardado
header('Content-Type: text/plain');

echo "=== TEST DE GUARDADO EN RENDER ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Simular el código que genera Claude
$test_shortcode = 'test_claude_' . time();
$test_code = '<?php
function ' . $test_shortcode . '_shortcode() {
    ob_start();
    ?>
    <div style="padding: 20px; background: #e3f2fd; border-radius: 8px;">
        <h3>✅ Código generado por Claude</h3>
        <p>Este código fue guardado exitosamente en Render!</p>
        <p>Shortcode: [' . $test_shortcode . ']</p>
        <p>Timestamp: ' . date('Y-m-d H:i:s') . '</p>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode("' . $test_shortcode . '", "' . $test_shortcode . '_shortcode");';

echo "1. Código de prueba generado:\n";
echo "   - Shortcode: " . $test_shortcode . "\n";
echo "   - Código length: " . strlen($test_code) . " bytes\n\n";

// PASO 1: Probar guardado directo en snippets
echo "2. Probando guardado DIRECTO en snippets...\n";

$snippets_dir = __DIR__ . '/snippets/';
echo "   - Directorio snippets: " . $snippets_dir . "\n";
echo "   - Existe: " . (is_dir($snippets_dir) ? 'SÍ' : 'NO') . "\n";
echo "   - Escribible: " . (is_writable($snippets_dir) ? 'SÍ' : 'NO') . "\n";

if (!is_dir($snippets_dir)) {
    echo "   ❌ PROBLEMA: Directorio snippets no existe\n";
    echo "   🔧 Creando directorio...\n";
    $mkdir_result = mkdir($snippets_dir, 0777, true);
    echo "   - Resultado: " . ($mkdir_result ? 'ÉXITO' : 'FALLÓ') . "\n";
}

if (is_dir($snippets_dir)) {
    $timestamp = time();
    $filename = $test_shortcode . '_' . $timestamp . '.php';
    $filepath = $snippets_dir . $filename;
    
    echo "   📝 Guardando archivo: " . $filename . "\n";
    
    $write_result = file_put_contents($filepath, $test_code);
    
    if ($write_result) {
        echo "   ✅ ÉXITO: Archivo guardado (" . $write_result . " bytes)\n";
        echo "   📄 Archivo existe: " . (file_exists($filepath) ? 'SÍ' : 'NO') . "\n";
        echo "   📏 Tamaño real: " . filesize($filepath) . " bytes\n";
    } else {
        echo "   ❌ ERROR: No se pudo guardar el archivo\n";
    }
} else {
    echo "   ❌ PROBLEMA: No se pudo crear/acceder al directorio snippets\n";
}

echo "\n3. Probando guardado vía API save-snippet.php...\n";

// PASO 2: Probar el endpoint save-snippet.php
$api_url = 'http://localhost/save-snippet.php';
if (isset($_SERVER['HTTP_HOST'])) {
    $api_url = 'https://' . $_SERVER['HTTP_HOST'] . '/save-snippet.php';
}

echo "   🌐 URL API: " . $api_url . "\n";

$payload = [
    'action' => 'save',
    'shortcode' => $test_shortcode . '_api',
    'code' => base64_encode($test_code),
    'user_id' => 1,
    'timestamp' => time()
];

echo "   📤 Payload preparado:\n";
echo "   - Shortcode: " . $payload['shortcode'] . "\n";
echo "   - Código encoded length: " . strlen($payload['code']) . "\n";

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-Key: lumina-secure-key-2024'
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30
]);

$api_response = curl_exec($curl);
$api_http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$api_error = curl_error($curl);
curl_close($curl);

echo "   📥 Respuesta API:\n";
echo "   - HTTP Code: " . $api_http_code . "\n";
echo "   - CURL Error: " . ($api_error ?: 'Ninguno') . "\n";
echo "   - Response Length: " . strlen($api_response) . "\n";

if ($api_response) {
    echo "   - Response Preview: " . substr($api_response, 0, 200) . "\n";
    
    $api_data = json_decode($api_response, true);
    if ($api_data) {
        echo "   📊 Respuesta decodificada:\n";
        foreach ($api_data as $key => $value) {
            if (is_string($value) && strlen($value) > 50) {
                echo "     - " . $key . ": " . substr($value, 0, 50) . "...\n";
            } else {
                echo "     - " . $key . ": " . $value . "\n";
            }
        }
    } else {
        echo "   ❌ Error decodificando JSON: " . json_last_error_msg() . "\n";
    }
}

echo "\n4. Verificando archivos en snippets después de ambas pruebas...\n";

if (is_dir($snippets_dir)) {
    $files = scandir($snippets_dir);
    $php_files = array_filter($files, function($f) {
        return pathinfo($f, PATHINFO_EXTENSION) === 'php';
    });
    
    echo "   📁 Archivos PHP en snippets: " . count($php_files) . "\n";
    
    foreach ($php_files as $file) {
        $file_path = $snippets_dir . $file;
        echo "     - " . $file . " (" . filesize($file_path) . " bytes, " . date('H:i:s', filemtime($file_path)) . ")\n";
    }
    
    if (count($php_files) === 0) {
        echo "   ⚠️ PROBLEMA: No hay archivos PHP en snippets\n";
    }
} else {
    echo "   ❌ PROBLEMA: Directorio snippets no accesible\n";
}

echo "\n5. Test de ejecución (si hay archivos)...\n";

if (!empty($php_files)) {
    $test_file = $php_files[0];
    echo "   🧪 Probando ejecución de: " . $test_file . "\n";
    
    // Extraer shortcode del nombre del archivo
    $shortcode_from_file = explode('_', pathinfo($test_file, PATHINFO_FILENAME))[0] . '_' . explode('_', pathinfo($test_file, PATHINFO_FILENAME))[1] . '_' . explode('_', pathinfo($test_file, PATHINFO_FILENAME))[2];
    
    $execute_payload = [
        'shortcode' => $shortcode_from_file,
        'attributes' => [],
        'user_id' => 1,
        'timestamp' => time()
    ];
    
    $execute_url = str_replace('save-snippet.php', 'execute-snippet.php', $api_url);
    echo "   🌐 Execute URL: " . $execute_url . "\n";
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $execute_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: lumina-secure-key-2024'
        ],
        CURLOPT_POSTFIELDS => json_encode($execute_payload),
        CURLOPT_TIMEOUT => 30
    ]);
    
    $execute_response = curl_exec($curl);
    $execute_http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    echo "   📥 Execute Response:\n";
    echo "   - HTTP Code: " . $execute_http_code . "\n";
    echo "   - Response Length: " . strlen($execute_response) . "\n";
    echo "   - Response Preview: " . substr($execute_response, 0, 200) . "\n";
    
    $execute_data = json_decode($execute_response, true);
    if ($execute_data) {
        echo "   ✅ JSON válido: " . ($execute_data['success'] ? 'ÉXITO' : 'ERROR') . "\n";
        if (isset($execute_data['error'])) {
            echo "   ❌ Error: " . $execute_data['error'] . "\n";
        }
    } else {
        echo "   ❌ JSON inválido: " . json_last_error_msg() . "\n";
    }
}

echo "\n=== TEST COMPLETADO ===\n";
echo "Este test ayudará a identificar dónde falla el guardado en Render\n";
?>
