<?php
// simple-save-test.php - Test simple de guardado
header('Content-Type: text/plain');

echo "=== TEST SIMPLE DE GUARDADO ===\n";

$snippets_dir = __DIR__ . '/snippets/';

echo "1. Directorio snippets: " . $snippets_dir . "\n";
echo "   - Existe: " . (is_dir($snippets_dir) ? 'SÍ' : 'NO') . "\n";
echo "   - Escribible: " . (is_writable($snippets_dir) ? 'SÍ' : 'NO') . "\n";

if (!is_dir($snippets_dir)) {
    echo "   - Creando directorio...\n";
    $result = mkdir($snippets_dir, 0777, true);
    echo "   - Resultado: " . ($result ? 'ÉXITO' : 'FALLÓ') . "\n";
}

if (is_dir($snippets_dir) && is_writable($snippets_dir)) {
    $test_file = $snippets_dir . 'test_' . time() . '.php';
    $test_content = '<?php echo "Test funcionando"; ?>';
    
    echo "\n2. Guardando archivo de prueba...\n";
    echo "   - Archivo: " . basename($test_file) . "\n";
    
    $write_result = file_put_contents($test_file, $test_content);
    
    if ($write_result) {
        echo "   - ✅ ÉXITO: " . $write_result . " bytes escritos\n";
        echo "   - Archivo existe: " . (file_exists($test_file) ? 'SÍ' : 'NO') . "\n";
        
        // Contar archivos total
        $files = glob($snippets_dir . '*.php');
        echo "   - Total archivos PHP: " . count($files) . "\n";
        
        // Mostrar archivos
        foreach ($files as $file) {
            echo "     * " . basename($file) . " (" . filesize($file) . " bytes)\n";
        }
        
    } else {
        echo "   - ❌ ERROR: No se pudo escribir\n";
    }
} else {
    echo "\n❌ PROBLEMA: No se puede escribir en snippets\n";
}

echo "\n=== FIN DEL TEST ===\n";
?>
