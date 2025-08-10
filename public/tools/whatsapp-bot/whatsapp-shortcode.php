<?php
/**
 * WhatsApp Bot Shortcode para WordPress
 * Uso: [whatsapp_bot]
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Registrar el shortcode
add_shortcode('whatsapp_bot', 'whatsapp_bot_shortcode');

function whatsapp_bot_shortcode($atts) {
    // Parámetros del shortcode
    $atts = shortcode_atts([
        'server_url' => 'https://tu-app.onrender.com', // URL de tu Render
        'width' => '100%',
        'height' => '600px',
        'title' => 'WhatsApp Bot Control Panel'
    ], $atts);

    // Generar ID único para el contenedor
    $container_id = 'whatsapp-bot-' . uniqid();
    
    ob_start();
    ?>
    
    <div id="<?php echo $container_id; ?>" style="width: <?php echo $atts['width']; ?>; border: 1px solid #ddd; border-radius: 10px; overflow: hidden;">
        <div class="whatsapp-bot-header" style="background: #25d366; color: white; padding: 15px; text-align: center;">
            <h3 style="margin: 0; color: white;"><?php echo $atts['title']; ?></h3>
        </div>
        
        <div class="whatsapp-bot-content" style="padding: 20px; background: #f9f9f9;">
            <div id="status-<?php echo $container_id; ?>" class="bot-status" style="margin: 10px 0; padding: 10px; border-radius: 5px; text-align: center; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;">
                Estado: Verificando...
            </div>
            
            <div class="bot-controls" style="text-align: center; margin: 20px 0;">
                <button onclick="startBot<?php echo $container_id; ?>()" class="wp-bot-btn" style="background: #25d366; color: white; padding: 10px 15px; border: none; border-radius: 5px; margin: 5px; cursor: pointer;">🚀 Iniciar Bot</button>
                <button onclick="stopBot<?php echo $container_id; ?>()" class="wp-bot-btn" style="background: #dc3545; color: white; padding: 10px 15px; border: none; border-radius: 5px; margin: 5px; cursor: pointer;">🛑 Detener Bot</button>
                <button onclick="getQR<?php echo $container_id; ?>()" class="wp-bot-btn" style="background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px; margin: 5px; cursor: pointer;">📱 Obtener QR</button>
                <button onclick="checkStatus<?php echo $container_id; ?>()" class="wp-bot-btn" style="background: #6c757d; color: white; padding: 10px 15px; border: none; border-radius: 5px; margin: 5px; cursor: pointer;">🔄 Estado</button>
            </div>
            
            <div id="qr-<?php echo $container_id; ?>" class="qr-container" style="text-align: center; margin: 20px 0;">
                <!-- QR code aparecerá aquí -->
            </div>
            
            <div id="messages-<?php echo $container_id; ?>" class="messages" style="margin: 20px 0;">
                <!-- Mensajes aparecerán aquí -->
            </div>
            
            <div class="bot-info" style="background: white; padding: 15px; border-radius: 5px; margin-top: 20px;">
                <h4 style="margin-top: 0;">📋 Instrucciones:</h4>
                <ol style="margin: 10px 0; padding-left: 20px;">
                    <li>Haz clic en "🚀 Iniciar Bot" para activar el sistema</li>
                    <li>Luego haz clic en "📱 Obtener QR" para ver el código QR</li>
                    <li>Escanea el QR con WhatsApp desde tu teléfono</li>
                    <li>¡Tu bot estará listo para responder mensajes automáticamente!</li>
                </ol>
                
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin-top: 10px;">
                    <strong>💡 Tip:</strong> Una vez conectado, el bot funcionará 24/7 automáticamente según las configuraciones de tu Google Sheets.
                </div>
            </div>
        </div>
    </div>

    <style>
        .wp-bot-btn:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }
        
        .bot-status.connected {
            background: #d4edda !important;
            color: #155724 !important;
            border: 1px solid #c3e6cb !important;
        }
        
        .bot-status.disconnected {
            background: #f8d7da !important;
            color: #721c24 !important;
            border: 1px solid #f5c6cb !important;
        }
    </style>

    <script>
        const serverUrl<?php echo $container_id; ?> = '<?php echo $atts['server_url']; ?>';
        
        async function startBot<?php echo $container_id; ?>() {
            showMessage<?php echo $container_id; ?>('Iniciando bot...', 'info');
            try {
                const response = await fetch(serverUrl<?php echo $container_id; ?> + '/api/start', { 
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });
                const data = await response.json();
                showMessage<?php echo $container_id; ?>(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    setTimeout(() => getQR<?php echo $container_id; ?>(), 3000);
                }
            } catch (error) {
                showMessage<?php echo $container_id; ?>('Error de conexión: ' + error.message, 'error');
            }
        }

        async function stopBot<?php echo $container_id; ?>() {
            try {
                const response = await fetch(serverUrl<?php echo $container_id; ?> + '/api/stop', { method: 'POST' });
                const data = await response.json();
                showMessage<?php echo $container_id; ?>(data.message, data.success ? 'success' : 'error');
                document.getElementById('qr-<?php echo $container_id; ?>').innerHTML = '';
            } catch (error) {
                showMessage<?php echo $container_id; ?>('Error de conexión: ' + error.message, 'error');
            }
        }

        async function getQR<?php echo $container_id; ?>() {
            showMessage<?php echo $container_id; ?>('Obteniendo código QR...', 'info');
            try {
                const response = await fetch(serverUrl<?php echo $container_id; ?> + '/api/qr');
                const data = await response.json();
                if (data.success) {
                    document.getElementById('qr-<?php echo $container_id; ?>').innerHTML = 
                        '<h4>📱 Escanea este código QR con WhatsApp:</h4><img src="' + data.qr + '" style="max-width: 250px; border: 1px solid #ddd; border-radius: 5px;" /><p style="font-size: 12px; color: #666;">El código QR se actualiza automáticamente cada pocos minutos</p>';
                    showMessage<?php echo $container_id; ?>('Código QR obtenido correctamente', 'success');
                } else {
                    showMessage<?php echo $container_id; ?>(data.message, 'error');
                }
            } catch (error) {
                showMessage<?php echo $container_id; ?>('Error de conexión: ' + error.message, 'error');
            }
        }

        async function checkStatus<?php echo $container_id; ?>() {
            try {
                const response = await fetch(serverUrl<?php echo $container_id; ?> + '/api/status');
                const data = await response.json();
                const statusDiv = document.getElementById('status-<?php echo $container_id; ?>');
                
                if (data.connected) {
                    statusDiv.className = 'bot-status connected';
                    statusDiv.innerHTML = '✅ Estado: Conectado<br><small>Número: ' + (data.botNumber || 'N/A') + ' | Mensajes procesados: ' + data.processedMessages + '</small>';
                    document.getElementById('qr-<?php echo $container_id; ?>').innerHTML = '<div style="color: green; font-weight: bold;">✅ Bot conectado y funcionando correctamente</div>';
                } else {
                    statusDiv.className = 'bot-status disconnected';
                    statusDiv.innerHTML = '❌ Estado: Desconectado<br><small>Mensajes pendientes: ' + (data.pendingMessages || 0) + '</small>';
                }
            } catch (error) {
                showMessage<?php echo $container_id; ?>('Error al verificar estado: ' + error.message, 'error');
            }
        }

        function showMessage<?php echo $container_id; ?>(message, type) {
            const div = document.getElementById('messages-<?php echo $container_id; ?>');
            const colors = {
                'success': '#d4edda',
                'error': '#f8d7da',
                'info': '#cce7ff'
            };
            const textColors = {
                'success': '#155724',
                'error': '#721c24',
                'info': '#0066cc'
            };
            
            div.innerHTML = '<div style="background: ' + colors[type] + '; color: ' + textColors[type] + '; padding: 10px; border-radius: 5px; margin: 10px 0;">' + message + '</div>';
            setTimeout(() => div.innerHTML = '', 8000);
        }

        // Verificar estado al cargar y cada 10 segundos
        checkStatus<?php echo $container_id; ?>();
        setInterval(() => checkStatus<?php echo $container_id; ?>(), 10000);
    </script>

    <?php
    return ob_get_clean();
}

// Hook para agregar el shortcode automáticamente si se detecta en el contenido
add_filter('the_content', 'auto_whatsapp_bot_shortcode');

function auto_whatsapp_bot_shortcode($content) {
    // Si el post contiene el shortcode [whatsapp_bot], asegurarse de que esté procesado
    if (has_shortcode($content, 'whatsapp_bot')) {
        return do_shortcode($content);
    }
    return $content;
}

?>