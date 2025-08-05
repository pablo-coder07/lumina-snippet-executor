<?php
// file-viewer.php MEJORADO con backup
header('Content-Type: text/html; charset=utf-8');

$snippets_dir = __DIR__ . '/snippets/';
$backup_file = __DIR__ . '/snippets_backup.json';

// Obtener lista de archivos
$files = [];
if (is_dir($snippets_dir)) {
    $scan = scandir($snippets_dir);
    foreach ($scan as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $file_path = $snippets_dir . $file;
            $files[] = [
                'name' => $file,
                'size' => filesize($file_path),
                'modified' => filemtime($file_path),
                'content_preview' => substr(file_get_contents($file_path), 0, 200)
            ];
        }
    }
}

// Ordenar por fecha de modificación (más recientes primero)
usort($files, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

// Estado del backup
$backup_exists = file_exists($backup_file);
$backup_size = $backup_exists ? filesize($backup_file) : 0;
$backup_modified = $backup_exists ? filemtime($backup_file) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lumina Snippets - File Viewer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: #334155;
            line-height: 1.6;
        }
        
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 20px rgba(59, 130, 246, 0.3);
        }
        
        .header h1 {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .actions {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        
        .backup-section {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        
        .btn-success:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }
        
        .backup-status {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 8px;
            padding: 1rem;
            font-size: 0.9rem;
        }
        
        .backup-status.exists {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }
        
        .backup-status.missing {
            background: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
        }
        
        .files-grid {
            display: grid;
            gap: 1rem;
        }
        
        .file-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .file-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .file-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .file-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }
        
        .file-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .file-content {
            padding: 1.5rem;
        }
        
        .code-preview {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            line-height: 1.5;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }
        
        .empty-state h3 {
            margin-bottom: 1rem;
            color: #475569;
        }
        
        @media (max-width: 768px) {
            .actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .backup-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📁 Lumina Snippets Viewer</h1>
        <p>Administrador de archivos PHP generados por DrawCode AI</p>
    </div>
    
    <div class="container">
        <!-- Sección de Backup -->
        <div class="actions">
            <div class="backup-section">
                <a href="/backup-restore.php?action=backup" class="btn btn-primary">
                    💾 Crear Backup
                </a>
                <a href="/backup-restore.php?action=restore" class="btn btn-success">
                    🔄 Restaurar desde Backup
                </a>
                <a href="/backup-restore.php?action=status" class="btn btn-warning">
                    📊 Estado del Backup
                </a>
            </div>
            
            <div class="backup-status <?php echo $backup_exists ? 'exists' : 'missing'; ?>">
                <?php if ($backup_exists): ?>
                    ✅ <strong>Backup disponible</strong><br>
                    Tamaño: <?php echo number_format($backup_size / 1024, 1); ?> KB<br>
                    Actualizado: <?php echo date('Y-m-d H:i:s', $backup_modified); ?>
                <?php else: ?>
                    ⚠️ <strong>Sin backup</strong><br>
                    Recomendado crear backup antes de redeploy
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo count($files); ?></div>
                <div class="stat-label">Archivos PHP</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $backup_exists ? 'SÍ' : 'NO'; ?></div>
                <div class="stat-label">Backup Disponible</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format(array_sum(array_column($files, 'size')) / 1024, 1); ?> KB</div>
                <div class="stat-label">Tamaño Total</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo !empty($files) ? date('H:i', max(array_column($files, 'modified'))) : 'N/A'; ?></div>
                <div class="stat-label">Último Archivo</div>
            </div>
        </div>
        
        <!-- Lista de Archivos -->
        <?php if (empty($files)): ?>
            <div class="empty-state">
                <h3>📂 No hay archivos PHP</h3>
                <p>Los archivos generados por DrawCode aparecerán aquí.</p>
                <p>Si acabas de hacer redeploy, usa <strong>"Restaurar desde Backup"</strong> para recuperar archivos.</p>
            </div>
        <?php else: ?>
            <div class="files-grid">
                <?php foreach ($files as $file): ?>
                    <div class="file-card">
                        <div class="file-header">
                            <div class="file-name">📄 <?php echo htmlspecialchars($file['name']); ?></div>
                            <div class="file-meta">
                                <span>📏 <?php echo number_format($file['size']); ?> bytes</span>
                                <span>📅 <?php echo date('Y-m-d H:i:s', $file['modified']); ?></span>
                                <span>⏰ <?php echo date('H:i', $file['modified']); ?></span>
                            </div>
                        </div>
                        <div class="file-content">
                            <div class="code-preview"><?php echo htmlspecialchars($file['content_preview']); ?>...</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Auto-refresh cada 30 segundos
        setTimeout(() => {
            location.reload();
        }, 30000);
        
        // Mostrar confirmación para acciones de backup
        document.querySelectorAll('.btn').forEach(btn => {
            if (btn.href && (btn.href.includes('backup') || btn.href.includes('restore'))) {
                btn.addEventListener('click', (e) => {
                    const action = btn.textContent.trim();
                    if (!confirm(`¿Confirmar acción: ${action}?`)) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>
