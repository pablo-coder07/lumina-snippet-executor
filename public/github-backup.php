<?php
// github-backup.php - Sistema de backup automático a GitHub
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

class GitHubBackup {
    private $github_token;
    private $github_user;
    private $github_repo;
    private $github_branch;
    private $backup_folder;
    
    public function __construct() {
        // CONFIGURACIÓN - Reemplaza con tu token de GitHub
        $this->github_token = 'TU_GITHUB_TOKEN_AQUI'; // Reemplazar con el token real
        $this->github_user = 'pablo-coder07';
        $this->github_repo = 'lumina-snippet-executor';
        $this->github_branch = 'main';
        $this->backup_folder = 'snippets-backup';
    }
    
    /**
     * Función de logging
     */
    private function debug_log($message, $data = null) {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] GITHUB-BACKUP: {$message}";
        if ($data !== null) {
            $log_entry .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        error_log($log_entry);
    }
    
    /**
     * Realizar petición a la API de GitHub
     */
    private function github_request($endpoint, $method = 'GET', $data = null) {
        $url = "https://api.github.com/repos/{$this->github_user}/{$this->github_repo}" . $endpoint;
        
        $headers = [
            'Authorization: token ' . $this->github_token,
            'User-Agent: Lumina-Backup-System',
            'Accept: application/vnd.github.v3+json',
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            $this->debug_log("CURL Error", ['error' => $curl_error, 'url' => $url]);
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
            $this->debug_log("GitHub API Error", [
                'http_code' => $http_code,
                'response' => $decoded_response
            ]);
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
            'message' => "🔄 Backup automático: {$filename} - " . date('Y-m-d H:i:s'),
            'content' => base64_encode($file_content),
            'branch' => $this->github_branch
        ];
        
        // Si el archivo ya existe, incluir SHA para actualización
        if ($existing_sha) {
            $commit_data['sha'] = $existing_sha;
            $this->debug_log("Archivo existe en GitHub, actualizando", ['sha' => $existing_sha]);
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
                'commit_sha' => $response['commit']['sha'] ?? 'unknown'
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
            
            // Pequeña pausa para no saturar la API
            usleep(500000); // 0.5 segundos
        }
        
        $result = [
            'success' => true,
            'files_backed_up' => $backed_up,
            'total_files' => count($files),
            'errors' => $errors,
            'backup_time' => date('Y-m-d H:i:s')
        ];
        
        $this->debug_log("=== BACKUP COMPLETO FINALIZADO ===", $result);
        
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
            'restore_time' => date('Y-m-d H:i:s')
        ];
        
        $this->debug_log("=== RESTAURACIÓN COMPLETA FINALIZADA ===", $result);
        
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
            'sync_status' => 'unknown'
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
            
        default:
            echo json_encode(['success' => false, 'error' => 'Acción no válida']);
            break;
    }
    exit;
}

// Si no es POST, mostrar interfaz simple
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔄 GitHub Backup System</title>
    <meta charset="utf-8">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .btn { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 4px; margin: 5px; cursor: pointer; }
        .btn:hover { background: #2563eb; }
        .btn-success { background: #10b981; }
        .btn-warning { background: #f59e0b; }
        .btn-danger { background: #ef4444; }
        .result { margin: 20px 0; padding: 15px; border-radius: 6px; }
        .success { background: #d1fae5; border: 1px solid #10b981; }
        .error { background: #fee2e2; border: 1px solid #ef4444; }
        .loading { background: #fef3c7; border: 1px solid #f59e0b; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Sistema de Backup GitHub</h1>
        <p>Gestiona backups automáticos de tus snippets en GitHub</p>
        
        <div>
            <button class="btn btn-warning" onclick="performAction('backup_all')">💾 Backup Completo</button>
            <button class="btn btn-success" onclick="performAction('restore_all')">🔄 Restaurar Todo</button>
            <button class="btn" onclick="performAction('status')">📊 Estado</button>
            <button class="btn" onclick="performAction('list_backups')">📄 Listar Backups</button>
        </div>
        
        <div id="result"></div>
    </div>

    <script>
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
                resultDiv.innerHTML = `<div class="result success">✅ ${JSON.stringify(data, null, 2)}</div>`;
            } else {
                resultDiv.innerHTML = `<div class="result error">❌ ${data.error || 'Error desconocido'}</div>`;
            }
        })
        .catch(error => {
            resultDiv.innerHTML = `<div class="result error">❌ Error: ${error.message}</div>`;
        });
    }
    </script>
</body>
</html>
