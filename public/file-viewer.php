<?php
// file-viewer.php MODIFICADO - Vista organizada por carpetas de usuario
$current_user = $_GET['user'] ?? null;
$show_file = $_GET['file'] ?? null;
?>
<!DOCTYPE html>
<html>
<head>
    <title>📁 Viewer Organizado - Snippets por Usuario</title>
    <meta charset="utf-8">
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .breadcrumb {
            background: #f1f5f9;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .breadcrumb a {
            color: #3b82f6;
            text-decoration: none;
        }
        .breadcrumb a:hover { text-decoration: underline; }
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
        .user-folders {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .user-folder {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .user-folder:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.1);
        }
        .user-folder-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 20px;
            text-align: center;
        }
        .user-folder-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .user-folder-name {
            font-weight: 700;
            font-size: 18px;
            color: #1e293b;
            margin-bottom: 5px;
        }
        .user-folder-stats {
            color: #64748b;
            font-size: 14px;
        }
        .file-list {
            margin-top: 20px;
        }
        .file-item {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 15px;
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
            max-height: 500px;
            overflow-y: auto;
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
            cursor: pointer;
        }
        .btn:hover { background: #2563eb; }
        .btn-back { background: #6b7280; }
        .btn-success { background: #10b981; }
        .actions {
            background: #fef3c7;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .no-content {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📁 Viewer Organizado por Usuario</h1>
        <p>Archivos PHP organizados por carpetas de usuario</p>
        <small>🕒 Generado: <?php echo date('Y-m-d H:i:s'); ?></small>
    </div>

    <?php
    $base_snippets_dir = __DIR__ . '/snippets/';
    
    // Verificar directorio base
    if (!is_dir($base_snippets_dir)) {
        echo '<div class="no-content">❌ Directorio /snippets/ no existe</div>';
        exit;
    }
    
    // Obtener todas las carpetas de usuarios
    $user_directories = glob($base_snippets_dir . 'usuario_*', GLOB_ONLYDIR);
    
    if (empty($user_directories)) {
        echo '<div class="no-content">
            <h3>📁 No hay carpetas de usuario</h3>
            <p>Las carpetas aparecerán aquí cuando generes código desde WordPress</p>
            <p>Formato esperado: <code>usuario_1</code>, <code>usuario_2</code>, etc.</p>
        </div>';
        exit;
    }
    ?>

    <!-- Breadcrumb Navigation -->
    <div class="breadcrumb">
        <a href="?">🏠 Inicio</a>
        <?php if ($current_user): ?>
            → <a href="?user=<?php echo urlencode($current_user); ?>">📂 <?php echo htmlspecialchars($current_user); ?></a>
        <?php endif; ?>
        <?php if ($show_file): ?>
            → <span>📄 <?php echo htmlspecialchars($show_file); ?></span>
        <?php endif; ?>
    </div>

    <div class="actions">
        <strong>🔗 Enlaces útiles:</strong>
        <a href="health.php" class="btn">📊 Health Check</a>
        <a href="verify.php" class="btn">🔍 Verify</a>
        <a href="simple-save-test.php" class="btn">🧪 Test Save</a>
        <a href="?" class="btn btn-back">🔄 Refresh</a>
    </div>

    <?php if (!$current_user): ?>
        <!-- VISTA PRINCIPAL: Mostrar carpetas de usuarios -->
        <?php
        // Recopilar estadísticas
        $total_users = count($user_directories);
        $total_files = 0;
        $user_stats = [];
        
        foreach ($user_directories as $user_dir) {
            $user_name = basename($user_dir);
            $php_files = glob($user_dir . '/*.php');
            $file_count = count($php_files);
            $total_files += $file_count;
            
            // Encontrar archivo más reciente
            $latest_file = null;
            $latest_timestamp = 0;
            foreach ($php_files as $file) {
                $file_time = filemtime($file);
                if ($file_time > $latest_timestamp) {
                    $latest_timestamp = $file_time;
                    $latest_file = $file;
                }
            }
            
            $user_stats[] = [
                'name' => $user_name,
                'files' => $file_count,
                'latest' => $latest_timestamp ? date('Y-m-d H:i', $latest_timestamp) : 'N/A',
                'latest_file' => $latest_file ? basename($latest_file) : null
            ];
        }
        
        // Ordenar por número de archivos (descendente)
        usort($user_stats, function($a, $b) {
            return $b['files'] - $a['files'];
        });
        ?>
        
        <div class="stats">
            <div class="stat-box">
                <strong>👥 Total de usuarios</strong><br>
                <?php echo $total_users; ?> carpetas
            </div>
            <div class="stat-box">
                <strong>📄 Total de archivos</strong><br>
                <?php echo $total_files; ?> archivos PHP
            </div>
            <div class="stat-box">
                <strong>📊 Promedio por usuario</strong><br>
                <?php echo round($total_files / max($total_users, 1), 1); ?> archivos
            </div>
            <div class="stat-box">
                <strong>👑 Usuario más activo</strong><br>
                <?php echo $user_stats[0]['name'] ?? 'N/A'; ?> (<?php echo $user_stats[0]['files'] ?? 0; ?> archivos)
            </div>
        </div>

        <h2>👥 Carpetas de Usuarios</h2>
        <div class="user-folders">
            <?php foreach ($user_stats as $user): ?>
                <div class="user-folder" onclick="location.href='?user=<?php echo urlencode($user['name']); ?>'">
                    <div class="user-folder-header">
                        <div class="user-folder-icon">👤</div>
                        <div class="user-folder-name"><?php echo htmlspecialchars($user['name']); ?></div>
                        <div class="user-folder-stats">
                            <?php echo $user['files']; ?> archivos<br>
                            <small>Último: <?php echo $user['latest']; ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- VISTA DE USUARIO: Mostrar archivos de un usuario específico -->
        <?php
        $user_dir = $base_snippets_dir . $current_user . '/';
        
        if (!is_dir($user_dir)) {
            echo '<div class="no-content">❌ Carpeta de usuario no encontrada: ' . htmlspecialchars($current_user) . '</div>';
            exit;
        }
        
        $user_files = glob($user_dir . '*.php');
        
        if (empty($user_files)) {
            echo '<div class="no-content">
                <h3>📁 No hay archivos en esta carpeta</h3>
                <p>Los archivos PHP aparecerán aquí cuando se genere código para este usuario</p>
                <a href="?" class="btn btn-back">← Volver al inicio</a>
            </div>';
            exit;
        }
        
        // Ordenar por fecha de modificación (más reciente primero)
        usort($user_files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Estadísticas del usuario
        $user_file_count = count($user_files);
        $user_total_size = array_sum(array_map('filesize', $user_files));
        $user_newest = filemtime($user_files[0]);
        $user_oldest = filemtime(end($user_files));
        
        // Extraer ID numérico del usuario
        $user_id = str_replace('usuario_', '', $current_user);
        ?>
        
        <div class="stats">
            <div class="stat-box">
                <strong>👤 Usuario</strong><br>
                ID: <?php echo htmlspecialchars($user_id); ?>
            </div>
            <div class="stat-box">
                <strong>📄 Archivos</strong><br>
                <?php echo $user_file_count; ?> archivos PHP
            </div>
            <div class="stat-box">
                <strong>💾 Tamaño total</strong><br>
                <?php echo number_format($user_total_size / 1024, 1); ?> KB
            </div>
            <div class="stat-box">
                <strong>🕒 Más reciente</strong><br>
                <?php echo date('Y-m-d H:i:s', $user_newest); ?>
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <a href="?" class="btn btn-back">← Volver al inicio</a>
            <span style="margin-left: 15px; color: #64748b;">
                Mostrando <?php echo $user_file_count; ?> archivo(s) de <strong><?php echo htmlspecialchars($current_user); ?></strong>
            </span>
        </div>

        <div class="file-list">
            <?php foreach ($user_files as $file): ?>
                <?php
                $filename = basename($file);
                $size = filesize($file);
                $modified = filemtime($file);
                $content = file_get_contents($file);
                
                // Extraer shortcode del contenido
                $shortcode_name = 'N/A';
                if (preg_match('/add_shortcode\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
                    $shortcode_name = $matches[1];
                }
                
                // Extraer metadatos del comentario inicial
                $created_date = 'N/A';
                if (preg_match('/\* Fecha: ([^\n]+)/', $content, $matches)) {
                    $created_date = trim($matches[1]);
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
                                   onclick="testExecution('<?php echo htmlspecialchars($shortcode_name); ?>', <?php echo $user_id; ?>)">
                                ▶️ Test
                            </button>
                            <a href="?user=<?php echo urlencode($current_user); ?>&file=<?php echo urlencode($filename); ?>" 
                               class="btn">👁️ Ver</a>
                        </div>
                    </div>
                    
                    <?php if ($show_file === $filename): ?>
                        <div class="file-content"><?php echo htmlspecialchars($content); ?></div>
                    <?php endif; ?>
                </div>
                
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>

<script>
function testExecution(shortcode, userId) {
    const payload = {
        shortcode: shortcode,
        user_id: userId,
        attributes: [],
        timestamp: Math.floor(Date.now() / 1000)
    };
    
    console.log('Testing shortcode:', shortcode, 'for user:', userId);
    
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
                      'Usuario: ' + data.user_directory + '\n' +
                      'Archivo: ' + data.file_used + '\n' +
                      'Tiempo: ' + data.execution_time + 'ms\n\n' +
                      'HTML: ' + data.html.substring(0, 100) + '...');
            } else {
                alert('❌ Error en ejecución:\n' + data.error + '\n\nArchivo: ' + (data.file_used || 'N/A'));
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

// Auto-abrir archivo si está en la URL
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const file = urlParams.get('file');
    if (file) {
        const fileElement = document.querySelector(`[href*="file=${encodeURIComponent(file)}"]`);
        if (fileElement) {
            fileElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
});
</script>

</body>
</html>
