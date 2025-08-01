<?php
// fix-snippets-directory.php - Subir a public/ y ejecutar una vez
header('Content-Type: application/json');

echo "=== INICIANDO REPARACIÓN DEL DIRECTORIO SNIPPETS ===\n";

$snippets_path = __DIR__ . '/snippets';
$backup_path = __DIR__ . '/snippets_backup_' . time();

// 1. Verificar estado actual
echo "1. Estado actual:\n";
echo "   - snippets existe: " . (file_exists($snippets_path) ? 'SÍ' : 'NO') . "\n";
echo "   - es archivo: " . (is_file($snippets_path) ? 'SÍ' : 'NO') . "\n";
echo "   - es directorio: " . (is_dir($snippets_path) ? 'SÍ' : 'NO') . "\n";

// 2. Si existe como archivo, hacer backup y eliminar
if (is_file($snippets_path)) {
    echo "2. Detectado archivo 'snippets', creando backup...\n";
    
    $copy_result = copy($snippets_path, $backup_path);
    echo "   - Backup creado: " . ($copy_result ? 'SÍ' : 'NO') . "\n";
    
    $unlink_result = unlink($snippets_path);
    echo "   - Archivo eliminado: " . ($unlink_result ? 'SÍ' : 'NO') . "\n";
}

// 3. Crear directorio snippets
echo "3. Creando directorio snippets...\n";
$mkdir_result = mkdir($snippets_path, 0777, true);
echo "   - Directorio creado: " . ($mkdir_result ? 'SÍ' : 'NO') . "\n";

// 4. Verificar permisos
if (is_dir($snippets_path)) {
    chmod($snippets_path, 0777);
    echo "   - Permisos aplicados: 0777\n";
    echo "   - Es escribible: " . (is_writable($snippets_path) ? 'SÍ' : 'NO') . "\n";
}

// 5. Crear archivo .gitkeep para mantener el directorio en git
$gitkeep_path = $snippets_path . '/.gitkeep';
file_put_contents($gitkeep_path, "# Mantener directorio en git\n");
echo "   - .gitkeep creado: " . (file_exists($gitkeep_path) ? 'SÍ' : 'NO') . "\n";

// 6. Verificar estado final
echo "4. Estado final:\n";
echo "   - snippets es directorio: " . (is_dir($snippets_path) ? 'SÍ' : 'NO') . "\n";
echo "   - snippets es escribible: " . (is_writable($snippets_path) ? 'SÍ' : 'NO') . "\n";

// 7. Test de escritura
$test_file = $snippets_path . '/test_' . time() . '.txt';
$write_test = file_put_contents($test_file, 'test content');
echo "   - Test de escritura: " . ($write_test !== false ? 'ÉXITO' : 'FALLÓ') . "\n";

if (file_exists($test_file)) {
    unlink($test_file);
    echo "   - Archivo de prueba eliminado\n";
}

echo "\n=== REPARACIÓN COMPLETADA ===\n";
echo "Ahora puedes ejecutar: https://lumina-snippet-executor.onrender.com/health.php\n";
?>
