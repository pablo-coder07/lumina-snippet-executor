<?php
// migrate-existing-files.php - Migrar archivos existentes a estructura organizada
header('Content-Type: text/plain');

echo "=== MIGRACIÓN DE ARCHIVOS EXISTENTES ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

$snippets_dir = __DIR__ . '/snippets/';

echo "1. Verificando archivos existentes en snippets/...\n";

if (!is_dir($snippets_dir)) {
    echo "   ❌ Directorio snippets no existe\n";
    exit;
}

// Buscar archivos PHP directamente en snippets/
$existing_files = glob($snippets_dir . '*.php');

if (empty($existing_files)) {
    echo "   ℹ️ No hay archivos PHP para migrar en el directorio raíz\n";
} else {
    echo "   📄 Archivos encontrados: " . count($existing_files) . "\n";
    
    foreach ($existing_files as $file) {
        echo "     - " . basename($file) . " (" . filesize($file) . " bytes)\n";
    }
    
    echo "\n2. Migrando archivos a carpeta usuario_1...\n";
    
    // Crear carpeta usuario_1 si no existe
    $user_dir = $snippets_dir . 'usuario_1/';
    if (!is_dir($user_dir)) {
        $mkdir_result = mkdir($user_dir, 0777, true);
        echo "   📁 Carpeta usuario_1 creada: " . ($mkdir_result ? 'SÍ' : 'NO') . "\n";
        
        if ($mkdir_result) {
            // Crear README
            $readme_content = "# Carpeta de códigos para Usuario 1\n\n";
            $readme_content .= "Fecha de migración: " . date('Y-m-d H:i:s') . "\n";
            $readme_content .= "Archivos migrados desde el directorio raíz.\n";
            file_put_contents($user_dir . 'README.md', $readme_content);
        }
    } else {
        echo "   📁 Carpeta usuario_1 ya existe\n";
    }
    
    $migrated = 0;
    $errors = 0;
    
    foreach ($existing_files as $source_file) {
        $filename = basename($source_file);
        $target_file = $user_dir . $filename;
        
        echo "   📄 Migrando: " . $filename . " ... ";
        
        // Verificar si ya existe en destino
        if (file_exists($target_file)) {
            echo "SKIP (ya existe)\n";
            continue;
        }
        
        // Leer contenido y agregar metadatos
        $content = file_get_contents($source_file);
        
        // Agregar metadatos al inicio si no los tiene
        if (strpos($content, 'Código generado por DrawCode AI') === false) {
            $metadata_comment = "<?php\n";
            $metadata_comment .= "/*\n";
            $metadata_comment .= " * Código generado por DrawCode AI\n";
            $metadata_comment .= " * Usuario: 1 (migrado)\n";
            $metadata_comment .= " * Archivo original: {$filename}\n";
            $metadata_comment .= " * Fecha de migración: " . date('Y-m-d H:i:s') . "\n";
            $metadata_comment .= " * Timestamp: " . time() . "\n";
            $metadata_comment .= " */\n\n";
            
            // Remover <?php del inicio y agregar metadatos
            $content = $metadata_comment . ltrim($content, "<?php \n");
        }
        
        // Copiar con metadatos
        $copy_result = file_put_contents($target_file, $content);
        
        if ($copy_result) {
            echo "SUCCESS (" . $copy_result . " bytes)\n";
            $migrated++;
            
            // Eliminar archivo original
            if (unlink($source_file)) {
                echo "     ✅ Archivo original eliminado\n";
            } else {
                echo "     ⚠️ No se pudo eliminar archivo original\n";
            }
        } else {
            echo "ERROR\n";
            $errors++;
        }
    }
    
    echo "\n   📊 Resultado de migración:\n";
    echo "   - Archivos migrados: " . $migrated . "\n";
    echo "   - Errores: " . $errors . "\n";
}

echo "\n3. Verificando estructura final...\n";

// Verificar carpetas de usuario existentes
$user_dirs = glob($snippets_dir . 'usuario_*', GLOB_ONLYDIR);

if (empty($user_dirs)) {
    echo "   ℹ️ No hay carpetas de usuario creadas aún\n";
} else {
    echo "   📁 Carpetas de usuario encontradas: " . count($user_dirs) . "\n";
    
    foreach ($user_dirs as $user_dir) {
        $user_name = basename($user_dir);
        $user_files = glob($user_dir . '/*.php');
        echo "     - " . $user_name . ": " . count($user_files) . " archivos\n";
        
        // Mostrar archivos
        foreach ($user_files as $file) {
            echo "       * " . basename($file) . " (" . filesize($file) . " bytes)\n";
        }
    }
}

echo "\n4. Verificando archivos sueltos restantes...\n";

$remaining_files = glob($snippets_dir . '*.php');
if (empty($remaining_files)) {
    echo "   ✅ No hay archivos sueltos - Migración completa\n";
} else {
    echo "   ⚠️ Archivos aún sin migrar: " . count($remaining_files) . "\n";
    foreach ($remaining_files as $file) {
        echo "     - " . basename($file) . "\n";
    }
}

echo "\n=== MIGRACIÓN COMPLETADA ===\n";
echo "Ahora puedes abrir file-viewer.php para ver los archivos organizados\n";
?>
