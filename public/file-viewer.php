<?php
// file-viewer.php - Lista de archivos con vista separada Y BACKUP
$view_file = $_GET['file'] ?? null;

// Si se solicita ver un archivo específico, mostrar solo ese archivo
if ($view_file) {
    $snippets_dir = __DIR__ . '/snippets/';
    $file_path = $snippets_dir . basename($view_file); // Sanitizar nombre
    
    if (!file_exists($file_path) || pathinfo($file_path, PATHINFO_EXTENSION) !== 'php') {
        header('HTTP/1.0 404 Not Found');
        echo '<h1>Archivo no encontrado</h1>';
        echo '<a href="file-viewer.php">← Volver al listado</a>';
        exit;
    }
    
    $content = file_get_contents($file_path);
    $size = filesize($file_path);
    $modified = date('Y-m-d H:i:s', filemtime($file_path));
    
    // Extraer shortcode
    $shortcode_name = 'N/A';
    if (preg_match('/add_shortcode\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
        $shortcode_name = $matches[1];
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>📄 <?php echo htmlspecialchars($view_file); ?> - Viewer</title>
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
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
            .file-info {
                background: #f1f5f9;
                padding: 15px;
                border-radius: 6px;
                margin-bottom: 20px;
                border-left: 4px solid #10b981;
            }
            .file-content {
                background: #1e293b;
                color: #e2e8f0;
                padding: 20px;
                border-radius: 8px;
                overflow-x: auto;
                font-family: 'Courier New', monospace;
                font-size: 13px;
                line-height: 1.5;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            .btn {
                background: #3b82f6;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                text-decoration: none;
                display: inline-block;
                margin-right: 10px;
                font-size: 14px;
            }
            .btn:hover { background: #2563eb; }
            .btn-back { background: #6b7280; }
            .btn-success { background: #10b981; }
            .btn-success:hover { background: #059669; }
            .btn-warning { background: #f59e0b; }
            .btn-warning:hover { background: #d97706; }
            .actions {
                margin-bottom: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📄 <?php echo htmlspecialchars($view_file); ?></h1>
                <p>Código completo del archivo</p>
            </div>
            
            <div class="actions">
                <a href="file-viewer.php" class="btn btn-back">← Volver al listado</a>
                <button class="btn btn-success" onclick="testExecution('<?php echo htmlspecialchars($shortcode_name); ?>')">
                    ▶️ Probar shortcode
                </button>
                <button class="btn" onclick="copyToClipboard()" id="copyBtn">
                    📋 Copiar código
                </button>
                <a href="backup-restore.php?action=backup" class="btn btn-warning">
                    💾 Backup Ahora
                </a>
            </div>
            
            <div class="file-info">
                <strong>📄 Archivo:</strong> <?php echo htmlspecialchars($view_file); ?><br>
                <strong>🏷️ Shortcode:</strong> [<?php echo htmlspecialchars($shortcode_name); ?>]<br>
                <strong>💾 Tamaño:</strong> <?php echo number_format($size); ?> bytes<br>
                <strong>🕒 Modificado:</strong> <?php echo $modified; ?>
            </div>
            
            <div class="file-content" id="fileContent"><?php echo htmlspecialchars($content); ?></div>
        </div>
        
        <script>
        function copyToClipboard() {
            const content = document.getElementById('fileContent').textContent;
            navigator.clipboard.writeText(content).then(function() {
                const btn = document.getElementById('copyBtn');
                const originalText = btn.innerHTML;
                btn.innerHTML = '✅ Copiado!';
                btn.style.background = '#10b981';
                setTimeout(function() {
                    btn.innerHTML = originalText;
                    btn.style.background = '#3b82f6';
                }, 2000);
            }).catch(function(err) {
                alert('Error al copiar: ' + err);
            });
        }
        
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
                        if (data.html && data.html.length > 0) {
                            alert('✅ Ejecución exitosa!\n\n' +
                                  'Archivo: ' + data.file_used + '\n' +
                                  'Tiempo: ' + data.execution_time + 'ms\n' +
                                  'HTML generado: ' + data.html.length + ' caracteres\n\n' +
                                  'Vista previa:\n' + data.html.substring(0, 200) + '...');
                        } else {
                            alert('⚠️ Ejecución exitosa pero sin contenido HTML!\n\n' +
                                  'Archivo: ' + data.file_used + '\n' +
                                  'Tiempo: ' + data.execution_time + 'ms\n' +
                                  'HTML length: ' + (data.html ? data.html.length : 0) + '\n' +
                                  'CSS length: ' + (data.css ? data.css.length : 0) + '\n' +
                                  'JS length: ' + (data.js ? data.js.length : 0));
                        }
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
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>📁 Listado de Snippets - Render</title>
    <meta charset="utf-8">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        .container { 
            max-width: 1000px; 
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
        .actions {
            background: #fef3c7;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        /* NUEVA SECCIÓN DE BACKUP */
        .backup-section {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #0ea5e9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .backup-section h3 {
            color: #0c4a6e;
            margin: 0 0 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .backup-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
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
            transition: all 0.2s ease;
        }
        .btn:hover { 
            background: #2563eb; 
            transform: translateY(-1px);
        }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        .btn-warning { background: #f59e0b; }
        .btn-warning:hover { background: #d97706; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        
        .file-list {
            background: #f8fafc;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .file-list-header {
            background: #1e293b;
            color: white;
            padding: 15px 20px;
            font-weight: 600;
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 20px;
            align-items: center;
        }
        .file-row {
            padding: 12px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 20px;
            align-items: center;
            transition: background-color 0.2s ease;
        }
        .file-row:hover {
            background: #f1f5f9;
        }
        .file-row:last-child {
            border-bottom: none;
        }
        .file-name {
            font-weight: 600;
            color: #1e293b;
            cursor: pointer;
        }
        .file-name:hover {
            color: #3b82f6;
            text-decoration: underline;
        }
        .file-size {
            font-size: 12px;
            color: #64748b;
            text-align: right;
        }
        .file-date {
            font-size: 12px;
            color: #64748b;
            text-align: right;
        }
        .file-shortcode {
            font-size: 11px;
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 8px;
            border-radius: 12px;
            font-family: monospace;
            text-align: center;
        }
        .no-files {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📁 Listado de Snippets</h1>
        <p>Archivos PHP generados por Claude - Haz clic en cualquier archivo para ver su código</p>
        <small>🕒 Actualizado: <?php echo date('Y-m-d H:i:s'); ?></small>
    </div>

    <!-- NUEVA SECCIÓN DE BACKUP -->
    <div class="backup-section">
        <h3>💾 Sistema de Backup y Restauración</h3>
        <p style="margin: 0 0 15px 0; color: #0c4a6e;">Protege tus archivos antes de hacer redeploy en Render</p>
        <div class="backup-actions">
            <a href="backup-restore.php?action=backup" class="btn btn-warning" onclick="return confirm('¿Crear backup de todos los archivos actuales?')">
                💾 Crear Backup Completo
            </a>
            <a href="backup-restore.php?action=restore" class="btn btn-success" onclick="return confirm('¿Restaurar archivos desde el backup? Esto sobrescribirá archivos existentes.')">
                🔄 Restaurar desde Backup
            </a>
            <a href="backup-restore.php?action=status" class="btn btn-primary">
                📊 Estado del Backup
            </a>
        </div>
        <small style="color: #0369a1; font-style: italic;">
            💡 Tip: Haz backup antes de cada redeploy para no perder tus códigos generados
        </small>
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
            <p><strong>Si acabas de hacer redeploy:</strong> Usa "🔄 Restaurar desde Backup" para recuperar tus archivos</p>
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
            <?php echo date('M j, H:i', $newest_file); ?>
        </div>
        <div class="stat-box">
            <strong>📅 Más antiguo</strong><br>
            <?php echo date('M j, H:i', $oldest_file); ?>
        </div>
    </div>

    <div class="file-list">
        <div class="file-list-header">
            <div>📄 Nombre del archivo</div>
            <div>🏷️ Shortcode</div>
            <div>💾 Tamaño</div>
            <div>🕒 Modificado</div>
        </div>
        
        <?php foreach ($files as $file): ?>
            <?php
            $filename = basename($file);
            $size = filesize($file);
            $modified = filemtime($file);
            
            // Leer solo las primeras líneas para extraer shortcode (más eficiente)
            $handle = fopen($file, 'r');
            $first_chunk = fread($handle, 2048);
            fclose($handle);
            
            // Extraer shortcode
            $shortcode_name = 'N/A';
            if (preg_match('/add_shortcode\s*\(\s*[\'"]([^\'"]+)[\'"]/', $first_chunk, $matches)) {
                $shortcode_name = $matches[1];
            }
            ?>
            
            <div class="file-row">
                <div class="file-name" onclick="location.href='?file=<?php echo urlencode($filename); ?>'">
                    <?php echo htmlspecialchars($filename); ?>
                </div>
                <div class="file-shortcode">
                    [<?php echo htmlspecialchars($shortcode_name); ?>]
                </div>
                <div class="file-size">
                    <?php echo number_format($size / 1024, 1); ?> KB
                </div>
                <div class="file-date">
                    <?php echo date('M j, H:i', $modified); ?>
                </div>
            </div>
            
        <?php endforeach; ?>
    </div>
</div>

<script>
// Auto-refresh cada 30 segundos
setTimeout(() => {
    location.reload();
}, 30000);
</script>

</body>
</html>
