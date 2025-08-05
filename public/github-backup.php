<?php
// github-backup.php - Sistema de backup automático a GitHub (VERSIÓN SEGURA)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Incluir configuración segura
require_once __DIR__ . '/config-secure.php';

class GitHubBackup {
    private $github_token;
    private $github_user;
    private $github_repo;
    private $github_branch;
    private $backup_folder;
    private $config_info;
    
    public function __construct() {
        try {
            // Cargar configuración de forma segura
            $this->github_token = SecureConfig::get('github_token');
            $this->github_user = SecureConfig::get('github_user');
            $this->github_repo = SecureConfig::get('github_repo');
            $this->github_branch = SecureConfig::get('github_branch');
            $this->backup_folder = SecureConfig::get('backup_folder');
            $this->config_info = SecureConfig::getInfo();
            
            // Validar configuración
            if (empty($this->github_token)) {
                throw new Exception('GitHub token no configurado. Verificar variables de entorno o configuración.');
            }
            
            $this->debug_log("GitHubBackup inicializado", [
                'config_source' => $this->config_info['config_source'],
                'is_secure' => $this->config_info['is_secure'],
                'token_present' => $this->config_info['token_present'],
                'repo' => $this->github_user . '/' . $this->github_repo
            ]);
            
        } catch (Exception $e) {
            $this->debug_log("ERROR: No se pudo inicializar GitHubBackup", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Función de logging
     */
    private function debug_log($message, $data = null) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] GITHUB-BACKUP: {$message}";
        if ($data !== null) {
            // Filtrar datos sensibles del log
            $safe_data = $this->sanitizeLogData($data);
            $log_entry .= " | Data: " . json_encode($safe_data, JSON_UNESCAPED_UNICODE);
        }
        error_log($log_entry);
    }
    
    /**
     * Sanitizar datos para logging (eliminar información sensible)
     */
    private function sanitizeLogData($data) {
        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                if (stripos($key, 'token') !== false || stripos($key, 'key') !== false || stripos($key, 'password') !== false) {
                    $sanitized[$key] = '[REDACTED]';
                } elseif (is_array($value)) {
                    $sanitized[$key] = $this->sanitizeLogData($value);
                } else {
                    $sanitized[$key] = $value;
                }
            }
            return $sanitized;
        }
        return $data;
    }
    
    /**
     * Realizar petición a la API de GitHub de forma segura
     */
    private function github_request($endpoint, $method = 'GET', $data = null) {
        $url = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}" . $endpoint;
        
        $headers = [
            'Authorization: token ' . $this->github_token,
            'User-Agent: Lumina-Backup-System-Secure',
            'Accept: application/vnd.github.v3+json',
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            $this->debug_log("CURL Error", ['error' => $curl_error, 'endpoint' => $endpoint]);
            return false;
        }
        
        $decoded_response = json_decode($response, true);
        
        $this->debug_log("GitHub API Request", [
            'method' => $method,
            'endpoint' => $endpoint,
            'http_code' => $http_code,
            'response_size' => strlen($response)
        ]);
        
        if ($http_code >= 400) {
            // Log error pero sin exponer detalles sensibles
            $error_info = [
                'http_code' => $http_code,
                'error_type' => $decoded_response['message'] ?? 'Unknown error'
            ];
            
            if ($http_code === 401) {
                $error_info['suggestion'] = 'Verificar token de GitHub y permisos';
            } elseif ($http_code === 403) {
                $error_info['suggestion'] = 'Rate limit alcanzado o permisos insuficientes';
            }
            
            $this->debug_log("GitHub API Error", $error_info);
            return false;
        }
        
        return $decoded_response;
    }
    
    /**
     * Obtener el SHA del archivo si existe
     */
    private function get_file_sha($file_path) {
        $endpoint = "/contents/{$this->backup_folder}/{$file_path}";
        $response = $this->github_request($endpoint);
        
        if ($response && isset($response['sha'])) {
            return $response['sha'];
        }
        
        return null;
    }
    
    /**
     * Backup de un archivo individual
     */
    public function backup_file($local_file_path, $filename) {
        $this->debug_log("=== INICIANDO BACKUP DE ARCHIVO ===", [
            'filename' => $filename,
            'local_path' => $local_file_path
        ]);
        
        // Verificar que el archivo local existe
        if (!file_exists($local_file_path)) {
            $this->debug_log("ERROR: Archivo local no existe", ['path' => $local_file_path]);
            return false;
        }
        
        // Leer contenido del archivo
        $file_content = file_get_contents($local_file_path);
        if ($file_content === false) {
            $this->debug_log("ERROR: No se pudo leer el archivo", ['path' => $local_file_path]);
            return false;
        }
        
        // Preparar datos para GitHub
        $github_path = "{$this->backup_folder}/{$filename}";
        $existing_sha = $this->get_file_sha($filename);
        
        $commit_data = [
            'message' => "🔄 Backup automático seguro: {$filename} - " . date('Y-m-d H:i:s'),
            'content' => base64_encode($file_content),
            'branch' => $this->github_branch
        ];
        
        // Si el archivo ya existe, incluir SHA para actualización
        if ($existing_sha) {
            $commit_data['sha'] = $existing_sha;
            $this->debug_log("Archivo existe en GitHub, actualizando", ['sha_present' => true]);
        } else {
            $this->debug_log("Archivo nuevo en GitHub");
        }
        
        // Hacer commit a GitHub
        $endpoint = "/contents/{$github_path}";
        $response = $this->github_request($endpoint, 'PUT', $commit_data);
        
        if ($response) {
            $this->debug_log("✅ Backup exitoso", [
                'filename' => $filename,
                'github_path' => $github_path,
                'commit_sha' => isset($response['commit']['sha']) ? 'present' : 'missing'
            ]);
            return true;
        } else {
            $this->debug_log("❌ Error en backup", ['filename' => $filename]);
            return false;
        }
    }
    
    /**
     * Backup de todos los archivos en la carpeta snippets
     */
    public function backup_all_snippets() {
        $this->debug_log("=== INICIANDO BACKUP COMPLETO ===");
        
        $snippets_dir = __DIR__ . '/snippets/';
        
        if (!is_dir($snippets_dir)) {
            $this->debug_log("ERROR: Directorio snippets no existe", ['path' => $snippets_dir]);
            return ['success' => false, 'error' => 'Directorio snippets no existe'];
        }
        
        $files = glob($snippets_dir . '*.php');
        
        if (empty($files)) {
            $this->debug_log("WARNING: No hay archivos PHP para backup");
            return ['success' => true, 'message' => 'No hay archivos para backup', 'files_backed_up' => 0];
        }
        
        $backed_up = 0;
        $errors = [];
        
        foreach ($files as $file_path) {
            $filename = basename($file_path);
            
            if ($this->backup_file($file_path, $filename)) {
                $backed_up++;
            } else {
                $errors[] = $filename;
            }
            
            // Pausa para respetar rate limits de GitHub
            usleep(500000); // 0.5 segundos
        }
        
        $result = [
            'success' => true,
            'files_backed_up' => $backed_up,
            'total_files' => count($files),
            'errors' => $errors,
            'backup_time' => date('Y-m-d H:i:s'),
            'config_info' => $this->config_info
        ];
        
        $this->debug_log("=== BACKUP COMPLETO FINALIZADO ===", [
            'files_backed_up' => $backed_up,
            'total_files' => count($files),
            'errors_count' => count($errors)
        ]);
        
        return $result;
    }
    
    /**
     * Listar archivos en el backup de GitHub
     */
    public function list_backup_files() {
        $this->debug_log("Listando archivos de backup en GitHub");
        
        $endpoint = "/contents/{$this->backup_folder}";
        $response = $this->github_request($endpoint);
        
        if (!$response || !is_array($response)) {
            $this->debug_log("ERROR: No se pudo obtener lista de backups");
            return false;
        }
        
        $files = [];
        foreach ($response as $item) {
            if ($item['type'] === 'file' && pathinfo($item['name'], PATHINFO_EXTENSION) === 'php') {
                $files[] = [
                    'name' => $item['name'],
                    'size' => $item['size'],
                    'sha' => $item['sha'],
                    'download_url' => $item['download_url']
                ];
            }
        }
        
        $this->debug_log("Archivos de backup encontrados", ['count' => count($files)]);
        
        return $files;
    }
    
    /**
     * Restaurar un archivo desde GitHub
     */
    public function restore_file($filename) {
        $this->debug_log("Restaurando archivo desde GitHub", ['filename' => $filename]);
        
        $endpoint = "/contents/{$this->backup_folder}/{$filename}";
        $response = $this->github_request($endpoint);
        
        if (!$response || !isset($response['content'])) {
            $this->debug_log("ERROR: No se pudo obtener archivo de GitHub", ['filename' => $filename]);
            return false;
        }
        
        // Decodificar contenido
        $content = base64_decode($response['content']);
        if ($content === false) {
            $this->debug_log("ERROR: No se pudo decodificar contenido", ['filename' => $filename]);
            return false;
        }
        
        // Guardar archivo localmente
        $snippets_dir = __DIR__ . '/snippets/';
        if (!is_dir($snippets_dir)) {
            mkdir($snippets_dir, 0755, true);
        }
        
        $local_path = $snippets_dir . $filename;
        $write_result = file_put_contents($local_path, $content);
        
        if ($write_result === false) {
            $this->debug_log("ERROR: No se pudo escribir archivo local", ['filename' => $filename]);
            return false;
        }
        
        $this->debug_log("✅ Archivo restaurado exitosamente", [
            'filename' => $filename,
            'local_path' => $local_path,
            'size' => $write_result
        ]);
        
        return true;
    }
    
    /**
     * Restaurar todos los archivos desde GitHub
     */
    public function restore_all_from_github() {
        $this->debug_log("=== INICIANDO RESTAURACIÓN COMPLETA ===");
        
        $backup_files = $this->list_backup_files();
        
        if (!$backup_files) {
            return ['success' => false, 'error' => 'No se pudo obtener lista de backups'];
        }
        
        if (empty($backup_files)) {
            return ['success' => true, 'message' => 'No hay archivos en el backup', 'files_restored' => 0];
        }
        
        $restored = 0;
        $errors = [];
        
        foreach ($backup_files as $file) {
            if ($this->restore_file($file['name'])) {
                $restored++;
            } else {
                $errors[] = $file['name'];
            }
            
            // Pequeña pausa
            usleep(200000); // 0.2 segundos
        }
        
        $result = [
            'success' => true,
            'files_restored' => $restored,
            'total_backup_files' => count($backup_files),
            'errors' => $errors,
            'restore_time' => date('Y-m-d H:i:s'),
            'config_info' => $this->config_info
        ];
        
        $this->debug_log("=== RESTAURACIÓN COMPLETA FINALIZADA ===", [
            'files_restored' => $restored,
            'total_backup_files' => count($backup_files),
            'errors_count' => count($errors)
        ]);
        
        return $result;
    }
    
    /**
     * Verificar estado del backup
     */
    public function get_backup_status() {
        $local_files = glob(__DIR__ . '/snippets/*.php');
        $backup_files = $this->list_backup_files();
        
        $status = [
            'local_files_count' => count($local_files),
            'backup_files_count' => $backup_files ? count($backup_files) : 0,
            'github_connection' => $backup_files !== false,
            'last_check' => date('Y-m-d H:i:s'),
            'sync_status' => 'unknown',
            'config_info' => $this->config_info,
            'security_status' => [
                'config_source' => $this->config_info['config_source'],
                'is_secure' => $this->config_info['is_secure'],
                'token_configured' => $this->config_info['token_present'],
                'recommendation' => $this->config_info['is_secure'] 
                    ? 'Configuración segura ✅' 
                    : 'Usar variables de entorno para mayor seguridad ⚠️'
            ]
        ];
        
        if ($backup_files !== false) {
            if (count($local_files) === count($backup_files)) {
                $status['sync_status'] = 'synchronized';
            } elseif (count($local_files) > count($backup_files)) {
                $status['sync_status'] = 'local_ahead';
            } else {
                $status['sync_status'] = 'backup_ahead';
            }
        } else {
            $status['sync_status'] = 'connection_error';
        }
        
        return $status;
    }
}

// Manejo de peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $action = $_POST['action'] ?? '';
        $backup = new GitHubBackup();
        
        switch ($action) {
            case 'backup_all':
                $result = $backup->backup_all_snippets();
                echo json_encode($result);
                break;
                
            case 'restore_all':
                $result = $backup->restore_all_from_github();
                echo json_encode($result);
                break;
                
            case 'status':
                $result = $backup->get_backup_status();
                echo json_encode($result);
                break;
                
            case 'list_backups':
                $result = $backup->list_backup_files();
                echo json_encode(['success' => true, 'files' => $result]);
                break;
                
            case 'backup_single':
                $filename = $_POST['filename'] ?? '';
                if (!empty($filename)) {
                    $filepath = __DIR__ . '/snippets/' . basename($filename);
                    $result = $backup->backup_file($filepath, $filename);
                    echo json_encode(['success' => $result]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Filename required']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'Acción no válida']);
                break;
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage(),
            'suggestion' => 'Verificar configuración de GitHub en Render'
        ]);
    }
    exit;
}

// Si no es POST, mostrar interfaz de administración
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔐 GitHub Backup System - Seguro</title>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 20px; background: #f8fafc; }
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .security-status { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .btn { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 4px; margin: 5px; cursor: pointer; }
        .btn:hover { background: #2563eb; }
        .btn-success { background: #10b981; }
        .btn-warning { background: #f59e0b; }
        .btn-danger { background: #ef4444; }
        .result { margin: 20px 0; padding: 15px; border-radius: 6px; }
        .success { background: #d1fae5; border: 1px solid #10b981; }
        .error { background: #fee2e2; border: 1px solid #ef4444; }
        .loading { background: #fef3c7; border: 1px solid #f59e0b; }
        .config-info { background: #f1f5f9; padding: 15px; border-radius: 6px; font-family: monospace; font-size: 12px; }
        .secure-badge { background: #10b981; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; }
        .warning-badge { background: #f59e0b; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card header">
            <h1>🔐 Sistema de Backup GitHub - Versión Segura</h1>
            <p>Gestiona backups automáticos con configuración protegida</p>
        </div>
        
        <div class="card security-status">
            <h3>🛡️ Estado de Seguridad</h3>
            <div id="securityInfo">Cargando información de seguridad...</div>
        </div>
        
        <div class="card">
            <h3>⚡ Acciones Rápidas</h3>
            <button class="btn btn-warning" onclick="performAction('backup_all')">💾 Backup Completo</button>
            <button class="btn btn-success" onclick="performAction('restore_all')">🔄 Restaurar Todo</button>
            <button class="btn" onclick="performAction('status')">📊 Estado</button>
            <button class="btn" onclick="performAction('list_backups')">📄 Listar Backups</button>
        </div>
        
        <div class="card">
            <h3>📋 Configuración del Sistema</h3>
            <div id="configInfo" class="config-info">
                Cargando configuración...
            </div>
        </div>
        
        <div id="result"></div>
    </div>

    <script>
    // Cargar información de seguridad al inicializar
    document.addEventListener('DOMContentLoaded', function() {
        loadSecurityInfo();
        performAction('status');
    });
    
    function loadSecurityInfo() {
        fetch('config-secure.php')
            .then(response => response.json())
            .then(data => {
                const securityDiv = document.getElementById('securityInfo');
                const configDiv = document.getElementById('configInfo');
                
                let securityHtml = '';
                let badge = data.is_secure ? 
                    '<span class="secure-badge">✅ SEGURO</span>' : 
                    '<span class="warning-badge">⚠️ MEJORAR</span>';
                
                securityHtml += `<p><strong>Estado:</strong> ${badge}</p>`;
                securityHtml += `<p><strong>Fuente de configuración:</strong> ${data.config_source}</p>`;
                
                if (data.config_source === 'environment') {
                    securityHtml += '<p>✅ Usando variables de entorno de Render (Recomendado)</p>';
                } else {
                    securityHtml += '<p>⚠️ Recomendación: Configurar variables de entorno en Render</p>';
                }
                
                securityDiv.innerHTML = securityHtml;
                
                // Mostrar configuración (sin información sensible)
                let configHtml = `
                    <strong>Repositorio:</strong> ${data.github_user}/${data.github_repo}<br>
                    <strong>Rama:</strong> ${data.github_branch}<br>
                    <strong>Carpeta backup:</strong> ${data.backup_folder}<br>
                    <strong>Token configurado:</strong> ${data.token_present ? '✅' : '❌'}<br>
                    <strong>Token (preview):</strong> ${data.token_prefix}<br>
                    <strong>Método de configuración:</strong> ${data.config_source}
                `;
                
                configDiv.innerHTML = configHtml;
            })
            .catch(error => {
                document.getElementById('securityInfo').innerHTML = 
                    '<p class="error">❌ Error al cargar información de seguridad</p>';
            });
    }
    
    function performAction(action) {
        const resultDiv = document.getElementById('result');
        resultDiv.innerHTML = '<div class="result loading">⏳ Procesando...</div>';
        
        const formData = new FormData();
        formData.append('action', action);
        
        fetch('github-backup.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let message = '✅ Acción completada exitosamente!<br><br>';
                
                if (action === 'backup_all') {
                    message += `📦 Archivos respaldados: ${data.files_backed_up}/${data.total_files}<br>`;
                    message += `🕒 Tiempo: ${data.backup_time}<br>`;
                    if (data.config_info) {
                        message += `🔐 Configuración: ${data.config_info.config_source}<br>`;
                    }
                    if (data.errors && data.errors.length > 0) {
                        message += `⚠️ Errores: ${data.errors.join(', ')}<br>`;
                    }
                } else if (action === 'restore_all') {
                    message += `🔄 Archivos restaurados: ${data.files_restored}/${data.total_backup_files}<br>`;
                    message += `🕒 Tiempo: ${data.restore_time}<br>`;
                    if (data.errors && data.errors.length > 0) {
                        message += `⚠️ Errores: ${data.errors.join(', ')}<br>`;
                    }
                    message += '<br>🔄 Recargando página en 3 segundos...';
                    setTimeout(() => location.reload(), 3000);
                } else if (action === 'status') {
                    message += `📊 Estado del sistema:<br>`;
                    message += `• Archivos locales: ${data.local_files_count}<br>`;
                    message += `• Archivos en backup: ${data.backup_files_count}<br>`;
                    message += `• Estado de sincronización: ${data.sync_status}<br>`;
                    message += `• Conexión GitHub: ${data.github_connection ? '✅' : '❌'}<br>`;
                    if (data.security_status) {
                        message += `• Seguridad: ${data.security_status.recommendation}<br>`;
                    }
                } else if (action === 'list_backups') {
                    message += `📄 Archivos en backup: ${data.files ? data.files.length : 0}<br>`;
                    if (data.files && data.files.length > 0) {
                        message += '<br>Archivos encontrados:<br>';
                        data.files.slice(0, 5).forEach(file => {
                            message += `• ${file.name} (${Math.round(file.size/1024)}KB)<br>`;
                        });
                        if (data.files.length > 5) {
                            message += `... y ${data.files.length - 5} más<br>`;
                        }
                    }
                }
                
                resultDiv.innerHTML = `<div class="result success">${message}</div>`;
                
            } else {
                let errorMsg = '❌ Error: ' + (data.error || 'Error desconocido');
                if (data.suggestion) {
                    errorMsg += '<br>💡 Sugerencia: ' + data.suggestion;
                }
                resultDiv.innerHTML = `<div class="result error">${errorMsg}</div>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="result error">❌ Error de conexión: ${error.message}</div>`;
        });
    }
    </script>
</body>
</html>
