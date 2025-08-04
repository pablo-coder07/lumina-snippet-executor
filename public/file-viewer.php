<?php
// file-viewer.php REVERTIDO - Vista simple como el original
?>
<!DOCTYPE html>
<html>
<head>
    <title>📁 Viewer de Snippets - Render</title>
    <meta charset="utf-8">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #3b82f6;
        }
        .file-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .file-header {
            background: #f1f5f9;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .file-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 16px;
        }
        .file-meta {
            font-size: 12px;
            color: #64748b;
        }
        .file-content {
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.4;
            max-height: 400px;
            overflow-y: auto;
        }
        .no-files {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }
        .actions {
            background: #fef3c7;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .btn {
            background: #3b82f6;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            font-size: 14px;
        }
        .btn:hover { background: #2563eb; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📁 Viewer de Snippets de Render</h1>
        <p>Archivos PHP generados por Claude y guardados en /snippets/</p>
        <small>🕒 Generado: <?php echo date('Y-m-d H:i:s'); ?></small>
    </div>

    <div class="actions">
        <strong>🔗 Enlaces útiles:</strong>
        <a href="health.php" class="btn">📊 Health Check</a>
        <a href="verify.php" class="btn">🔍 Verify</a>
        <a href="simple-save-test.php" class="btn">🧪 Test Save</a>
        <a href="?refresh=1" class="btn">🔄 Refresh</a>
        <a href="test-execution.php" class="btn btn-success">🚀 Test Execution</a>
    </div>

    <?php
    $snippets_dir = __DIR__ . '/snippets/';
    
    // Verificar directorio
    if (!is_dir($snippets_dir)) {
        echo '<div class="no-files">❌ Directorio /snippets/ no existe</div>';
        exit;
    }
    
    // Obtener archivos PHP
    $files = glob($snippets_dir . '*.php');
    
    if (empty($files)) {
        echo '<div class="no-files">
            <h3>📁 No hay archivos PHP en snippets</h3>
            <p>Los archivos aparecerán aquí cuando generes código desde WordPress</p>
        </div>';
        exit;
    }
    
    // Ordenar por fecha de modificación (más reciente primero)
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // Estadísticas
    $total_files = count($files);
    $total_size = array_sum(array_map('filesize', $files));
    $newest_file = filemtime($files[0]);
    $oldest_file = filemtime(end($files));
    ?>
    
    <div class="stats">
        <div class="stat-box">
            <strong>📄 Total de archivos</strong><br>
            <?php echo $total_files; ?> archivos PHP
        </div>
        <div class="stat-box">
            <strong>💾 Tamaño total</strong><br>
            <?php echo number_format($total_size / 1024, 1); ?> KB
        </div>
        <div class="stat-box">
            <strong>🕒 Más reciente</strong><br>
            <?php echo date('Y-m-d H:i:s', $newest_file); ?>
        </div>
        <div class="stat-box">
            <strong>📅 Más antiguo</strong><br>
            <?php echo date('Y-m-d H:i:s', $oldest_file); ?>
        </div>
    </div>

    <?php
    foreach ($files as $file) {
        $filename = basename($file);
        $size = filesize($file);
        $modified = filemtime($file);
        $content = file_get_contents($file);
        
        // Extraer shortcode del contenido
        $shortcode_name = 'N/A';
        if (preg_match('/add_shortcode\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
            $shortcode_name = $matches[1];
        }
        
        // Extraer fecha de creación del comentario
        $created_date = 'N/A';
        if (preg_match('/\* Fecha: ([^\n]+)/', $content, $matches)) {
            $created_date = $matches[1];
        }
        
        // Detectar tipo de contenido
        $has_html = strpos($content, '<div') !== false || strpos($content, '<h') !== false;
        $has_css = strpos($content, 'style') !== false;
        $has_js = strpos($content, 'script') !== false;
        
        $features = [];
        if ($has_html) $features[] = 'HTML';
        if ($has_css) $features[] = 'CSS';  
        if ($has_js) $features[] = 'JS';
        ?>
        
        <div class="file-item">
            <div class="file-header">
                <div>
                    <div class="file-name">📄 <?php echo htmlspecialchars($filename); ?></div>
                    <div class="file-meta">
                        <strong>Shortcode:</strong> [<?php echo htmlspecialchars($shortcode_name); ?>] | 
                        <strong>Tamaño:</strong> <?php echo number_format($size); ?> bytes | 
                        <strong>Modificado:</strong> <?php echo date('Y-m-d H:i:s', $modified); ?>
                        <?php if ($created_date !== 'N/A'): ?>
                            | <strong>Creado:</strong> <?php echo htmlspecialchars($created_date); ?>
                        <?php endif; ?>
                        <?php if (!empty($features)): ?>
                            | <strong>Contiene:</strong> <?php echo implode(', ', $features); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <button class="btn btn-success" 
                           onclick="testExecution('<?php echo htmlspecialchars($shortcode_name); ?>')">
                        ▶️ Test
                    </button>
                </div>
            </div>
            <div class="file-content"><?php echo htmlspecialchars($content); ?></div>
        </div>
        
        <?php
    }
    ?>
</div>

<script>
function testExecution(shortcode) {
    const payload = {
        shortcode: shortcode,
        attributes: [],
        timestamp: Math.floor(Date.now() / 1000)
    };
    
    console.log('Testing shortcode:', shortcode);
    
    fetch('execute-snippet.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-API-Key': 'lumina-secure-key-2024'
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.text())
    .then(text => {
        console.log('Raw response:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('✅ Ejecución exitosa!\n\n' +
                      'Archivo: ' + data.file_used + '\n' +
                      'Tiempo: ' + data.execution_time + 'ms\n\n' +
                      'HTML: ' + data.html.substring(0, 100) + '...');
            } else {
                alert('❌ Error en ejecución:\n' + data.error);
            }
        } catch (e) {
            alert('❌ JSON inválido. Ver consola para detalles.');
            console.error('JSON parse error:', e);
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('❌ Error de conexión: ' + error.message);
    });
}
</script>

</body>
</html>
