<?php
// config-secure.php - Configuración segura LIMPIA (versión para repositorio)
// Esta versión NO contiene tokens y es segura para subir

/**
 * Configuración de seguridad para GitHub Backup
 * Usa SOLO variables de entorno de Render
 */

class SecureConfig {
    private static $config = null;
    
    /**
     * Obtener configuración de forma segura
     */
    public static function get($key) {
        if (self::$config === null) {
            self::loadConfig();
        }
        
        return self::$config[$key] ?? null;
    }
    
    /**
     * Cargar configuración SOLO desde variables de entorno
     */
    private static function loadConfig() {
        self::$config = [];
        
        // Cargar desde variables de entorno de Render
        self::$config['github_token'] = getenv('GITHUB_TOKEN');
        self::$config['github_user'] = getenv('GITHUB_USER') ?: 'pablo-coder07';
        self::$config['github_repo'] = getenv('GITHUB_REPO') ?: 'lumina-snippet-executor';
        self::$config['github_branch'] = getenv('GITHUB_BRANCH') ?: 'main';
        self::$config['backup_folder'] = getenv('BACKUP_FOLDER') ?: 'snippets-backup';
        
        // Determinar fuente de configuración
        if (!empty(self::$config['github_token'])) {
            self::$config['config_source'] = 'environment';
        } else {
            self::$config['config_source'] = 'missing';
            error_log("[SECURE-CONFIG] WARNING: GITHUB_TOKEN no encontrado en variables de entorno");
        }
        
        // Log de inicialización (sin exponer datos sensibles)
        error_log("[SECURE-CONFIG] Configuración cargada desde: " . self::$config['config_source']);
    }
    
    /**
     * Verificar si la configuración es segura
     */
    public static function isSecure() {
        if (self::$config === null) {
            self::loadConfig();
        }
        
        return self::$config['config_source'] === 'environment';
    }
    
    /**
     * Obtener información de configuración (sin exponer credenciales)
     */
    public static function getInfo() {
        if (self::$config === null) {
            self::loadConfig();
        }
        
        $token = self::$config['github_token'] ?? '';
        
        return [
            'config_source' => self::$config['config_source'],
            'is_secure' => self::isSecure(),
            'github_user' => self::$config['github_user'],
            'github_repo' => self::$config['github_repo'],
            'github_branch' => self::$config['github_branch'],
            'backup_folder' => self::$config['backup_folder'],
            'token_present' => !empty($token),
            'token_length' => strlen($token),
            'token_prefix' => !empty($token) ? substr($token, 0, 7) . '...' : 'N/A',
            'environment_vars' => [
                'GITHUB_TOKEN' => !empty(getenv('GITHUB_TOKEN')) ? '✅ Configurado' : '❌ Faltante',
                'GITHUB_USER' => !empty(getenv('GITHUB_USER')) ? '✅ Configurado' : '⚠️ Usando default',
                'GITHUB_REPO' => !empty(getenv('GITHUB_REPO')) ? '✅ Configurado' : '⚠️ Usando default',
                'GITHUB_BRANCH' => !empty(getenv('GITHUB_BRANCH')) ? '✅ Configurado' : '⚠️ Usando default',
                'BACKUP_FOLDER' => !empty(getenv('BACKUP_FOLDER')) ? '✅ Configurado' : '⚠️ Usando default'
            ]
        ];
    }
    
    /**
     * Validar configuración completa
     */
    public static function validate() {
        $info = self::getInfo();
        
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'recommendations' => []
        ];
        
        // Validaciones críticas
        if (!$info['token_present']) {
            $validation['valid'] = false;
            $validation['errors'][] = 'GITHUB_TOKEN no configurado en variables de entorno';
        }
        
        if ($info['config_source'] !== 'environment') {
            $validation['valid'] = false;
            $validation['errors'][] = 'Configuración no carga desde variables de entorno';
        }
        
        // Advertencias
        if (empty(getenv('GITHUB_USER'))) {
            $validation['warnings'][] = 'GITHUB_USER no definido, usando default';
        }
        
        if (empty(getenv('GITHUB_REPO'))) {
            $validation['warnings'][] = 'GITHUB_REPO no definido, usando default';
        }
        
        // Recomendaciones
        if ($info['token_length'] < 40) {
            $validation['recommendations'][] = 'Token parece ser muy corto, verificar validez';
        }
        
        return $validation;
    }
}

// Test de configuración si se ejecuta directamente
if ($_SERVER['REQUEST_METHOD'] === 'GET' && basename($_SERVER['SCRIPT_NAME']) === 'config-secure.php') {
    header('Content-Type: application/json');
    
    try {
        $info = SecureConfig::getInfo();
        $validation = SecureConfig::validate();
        
        $response = [
            'status' => $validation['valid'] ? 'OK' : 'ERROR',
            'info' => $info,
            'validation' => $validation,
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => 'Render'
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'ERROR',
            'error' => $e->getMessage(),
            'suggestion' => 'Verificar variables de entorno en Render Dashboard',
            'required_vars' => [
                'GITHUB_TOKEN' => 'Tu token de GitHub',
                'GITHUB_USER' => 'pablo-coder07 (opcional)',
                'GITHUB_REPO' => 'lumina-snippet-executor (opcional)',
                'GITHUB_BRANCH' => 'main (opcional)',
                'BACKUP_FOLDER' => 'snippets-backup (opcional)'
            ]
        ], JSON_PRETTY_PRINT);
    }
}
?>
