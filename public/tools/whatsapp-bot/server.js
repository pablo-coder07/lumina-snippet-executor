const express = require('express');
const path = require('path');
const { Client, MessageMedia, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode');
const { google } = require('googleapis');
const stringSimilarity = require('string-similarity');

const app = express();
const port = process.env.PORT || 3000;

// Middleware
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));
app.use(express.urlencoded({ extended: true }));

// Variables globales del bot
let client = null;
let qrData = null;
let isReady = false;
let numeroBot = null;
const mensajesProcesados = new Set();
const ultimoMensajeBot = new Map();
const mensajesPendientes = [];
const historialUsuarios = new Map();

// Configuración de Google Sheets
const SHEET_ID = process.env.SHEET_ID || '1Sik4rqd1u7c8heQ6aA-TmNXw1UUiIOIOpSdqZC9OGrE';
const SHEET_NAME = process.env.SHEET_NAME || 'Sheet1';

// Configuración de credenciales de Google desde variables de entorno
const credentials = {
    type: process.env.GOOGLE_TYPE || 'service_account',
    project_id: process.env.GOOGLE_PROJECT_ID,
    private_key_id: process.env.GOOGLE_PRIVATE_KEY_ID,
    private_key: (process.env.GOOGLE_PRIVATE_KEY || '').replace(/\\n/g, '\n'),
    client_email: process.env.GOOGLE_CLIENT_EMAIL,
    client_id: process.env.GOOGLE_CLIENT_ID,
    auth_uri: process.env.GOOGLE_AUTH_URI || 'https://accounts.google.com/o/oauth2/auth',
    token_uri: process.env.GOOGLE_TOKEN_URI || 'https://oauth2.googleapis.com/token',
    auth_provider_x509_cert_url: process.env.GOOGLE_AUTH_PROVIDER_X509_CERT_URL || 'https://www.googleapis.com/oauth2/v1/certs',
    client_x509_cert_url: process.env.GOOGLE_CLIENT_X509_CERT_URL
};

// Configurar Google Auth
const auth = new google.auth.GoogleAuth({
    credentials: credentials,
    scopes: ['https://www.googleapis.com/auth/spreadsheets.readonly'],
});

const sheets = google.sheets({ version: 'v4', auth });

// ========================================
// ENDPOINTS DE LA API
// ========================================

// Endpoint de salud
app.get('/health', (req, res) => {
    res.json({ 
        status: 'ok', 
        whatsapp: isReady ? 'connected' : 'disconnected',
        timestamp: new Date().toISOString()
    });
});

// Obtener QR para conexión
app.get('/api/qr', async (req, res) => {
    if (isReady) {
        return res.json({ 
            success: false, 
            message: 'WhatsApp ya está conectado' 
        });
    }
    
    if (!qrData) {
        return res.json({ 
            success: false, 
            message: 'QR no disponible. Inicia el bot primero.' 
        });
    }
    
    try {
        const qrImage = await qrcode.toDataURL(qrData);
        res.json({ 
            success: true, 
            qr: qrImage,
            message: 'Escanea este QR con WhatsApp'
        });
    } catch (error) {
        res.status(500).json({ 
            success: false, 
            error: error.message 
        });
    }
});

// Estado del bot
app.get('/api/status', (req, res) => {
    res.json({
        connected: isReady,
        botNumber: numeroBot,
        pendingMessages: mensajesPendientes.length,
        processedMessages: mensajesProcesados.size,
        timestamp: new Date().toISOString()
    });
});

// Iniciar bot
app.post('/api/start', async (req, res) => {
    if (client) {
        return res.json({ 
            success: false, 
            message: 'Bot ya está iniciado' 
        });
    }
    
    try {
        await iniciarBot();
        res.json({ 
            success: true, 
            message: 'Bot iniciado correctamente. Usa /api/qr para obtener el código QR.' 
        });
    } catch (error) {
        res.status(500).json({ 
            success: false, 
            error: error.message 
        });
    }
});

// Detener bot
app.post('/api/stop', async (req, res) => {
    try {
        if (client) {
            await client.destroy();
            client = null;
            isReady = false;
            qrData = null;
            numeroBot = null;
        }
        res.json({ 
            success: true, 
            message: 'Bot detenido correctamente' 
        });
    } catch (error) {
        res.status(500).json({ 
            success: false, 
            error: error.message 
        });
    }
});

// Enviar mensaje manualmente
app.post('/api/send', async (req, res) => {
    const { to, message } = req.body;
    
    if (!isReady) {
        return res.status(400).json({ 
            success: false, 
            message: 'WhatsApp no está conectado' 
        });
    }
    
    if (!to || !message) {
        return res.status(400).json({ 
            success: false, 
            message: 'Se requiere "to" y "message"' 
        });
    }
    
    try {
        const destinatario = to.includes('@c.us') ? to : `${to}@c.us`;
        await client.sendMessage(destinatario, message);
        res.json({ 
            success: true, 
            message: 'Mensaje enviado correctamente' 
        });
    } catch (error) {
        res.status(500).json({ 
            success: false, 
            error: error.message 
        });
    }
});

// Interfaz web para QR
app.get('/', (req, res) => {
    res.send(`
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>WhatsApp Bot - Conexión</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            .container { text-align: center; }
            .btn { background: #25d366; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 10px; }
            .btn:hover { background: #128c7e; }
            .status { margin: 20px 0; padding: 10px; border-radius: 5px; }
            .connected { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .disconnected { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            #qrCode { margin: 20px 0; }
            img { max-width: 300px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🤖 WhatsApp Bot Control Panel</h1>
            <div id="status" class="status disconnected">Estado: Desconectado</div>
            
            <div>
                <button class="btn" onclick="startBot()">🚀 Iniciar Bot</button>
                <button class="btn" onclick="stopBot()">🛑 Detener Bot</button>
                <button class="btn" onclick="getQR()">📱 Obtener QR</button>
                <button class="btn" onclick="checkStatus()">🔄 Estado</button>
            </div>
            
            <div id="qrCode"></div>
            <div id="messages"></div>
        </div>

        <script>
            async function startBot() {
                const response = await fetch('/api/start', { method: 'POST' });
                const data = await response.json();
                showMessage(data.message, data.success);
                if (data.success) setTimeout(getQR, 2000);
            }

            async function stopBot() {
                const response = await fetch('/api/stop', { method: 'POST' });
                const data = await response.json();
                showMessage(data.message, data.success);
                document.getElementById('qrCode').innerHTML = '';
            }

            async function getQR() {
                const response = await fetch('/api/qr');
                const data = await response.json();
                if (data.success) {
                    document.getElementById('qrCode').innerHTML = 
                        '<h3>Escanea este código QR con WhatsApp:</h3><img src="' + data.qr + '" />';
                } else {
                    showMessage(data.message, false);
                }
            }

            async function checkStatus() {
                const response = await fetch('/api/status');
                const data = await response.json();
                const statusDiv = document.getElementById('status');
                if (data.connected) {
                    statusDiv.className = 'status connected';
                    statusDiv.innerHTML = 'Estado: Conectado ✅<br>Número: ' + (data.botNumber || 'N/A');
                    document.getElementById('qrCode').innerHTML = '';
                } else {
                    statusDiv.className = 'status disconnected';
                    statusDiv.innerHTML = 'Estado: Desconectado ❌';
                }
            }

            function showMessage(message, success) {
                const div = document.getElementById('messages');
                div.innerHTML = '<div style="color: ' + (success ? 'green' : 'red') + '; margin: 10px 0;">' + message + '</div>';
                setTimeout(() => div.innerHTML = '', 5000);
            }

            // Check status on load
            checkStatus();
            setInterval(checkStatus, 5000);
        </script>
    </body>
    </html>
    `);
});

// ========================================
// FUNCIONES DEL BOT (adaptadas)
// ========================================

async function iniciarBot() {
    if (client) return;

    client = new Client({
        authStrategy: new LocalAuth({
            clientId: 'bot1-cloud',
            dataPath: './whatsapp_sessions'
        }),
        puppeteer: {
            headless: true,
            timeout: 60000,
            args: [
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-accelerated-2d-canvas',
                '--no-first-run',
                '--no-zygote',
                '--disable-gpu',
                '--disable-web-security',
                '--disable-extensions',
                '--disable-plugins'
            ]
        }
    });

    client.on('qr', (qr) => {
        console.log('🎯 QR generado para conexión web');
        qrData = qr;
    });

    client.on('ready', () => {
        console.log('✅ WhatsApp Bot listo para recibir mensajes');
        isReady = true;
        qrData = null;
    });

    client.on('authenticated', () => {
        console.log('🔐 Cliente autenticado exitosamente');
    });

    client.on('auth_failure', msg => {
        console.error('❌ Error de autenticación:', msg);
        isReady = false;
        qrData = null;
    });

    client.on('disconnected', (reason) => {
        console.log('🚨 Cliente desconectado:', reason);
        isReady = false;
        qrData = null;
    });

    client.on('message', async message => {
        // Reutilizar la lógica existente del bot1.js
        const mensajeId = message.id._serialized;

        if (mensajesProcesados.has(mensajeId)) {
            return;
        }

        mensajesProcesados.add(mensajeId);
        await new Promise(resolve => setTimeout(resolve, 800));

        const numeroRemitente = message.from;
        const texto = message.body?.trim();

        if (!numeroBot && message.to) {
            numeroBot = message.to;
            console.log(`📲 Número del bot detectado: ${numeroBot}`);
        }

        if (!texto || !numeroRemitente.endsWith('@c.us') || numeroRemitente.includes('status@')) {
            return;
        }

        // Verificar horarios (simplificado)
        const ahora = new Date();
        const hora = ahora.getHours();
        const enHorarioLaboral = hora >= 7 && hora <= 22;

        if (!enHorarioLaboral) {
            mensajesPendientes.push({
                numeroRemitente,
                texto,
                numeroBot,
                timestamp: ahora.toISOString()
            });
            return;
        }

        const esPrimeraConversacion = !historialUsuarios.has(numeroRemitente);
        if (esPrimeraConversacion) {
            historialUsuarios.set(numeroRemitente, true);
        }

        const respuesta = await procesarMensaje(numeroBot, texto, numeroRemitente, esPrimeraConversacion);

        if (respuesta) {
            if (respuesta.toLowerCase().includes('.jpeg') || respuesta.toLowerCase().includes('.jpg') || respuesta.toLowerCase().includes('.png')) {
                await enviarImagen(message.from, respuesta);
            } else {
                await client.sendMessage(message.from, respuesta);
            }
            
            ultimoMensajeBot.set(numeroRemitente, respuesta);
        }
    });

    await client.initialize();
}

async function procesarMensaje(numeroBot, mensaje, numeroRemitente, esPrimeraConversacion = false) {
    try {
        const datos = await sheets.spreadsheets.values.get({
            spreadsheetId: SHEET_ID,
            range: `${SHEET_NAME}!A2:I`,
        });

        const filas = datos.data.values || [];
        const numeroLimpio = numeroBot.replace('@c.us', '');

        const filaCoincidente = filas.find(f => f[5] && f[5].includes(numeroLimpio));
        if (!filaCoincidente) {
            return null;
        }

        const idUsuario = filaCoincidente[1];

        // Lógica de primera conversación
        if (esPrimeraConversacion) {
            const primerasConversaciones = filas
                .filter(f => f[1] === idUsuario && f[7] && f[8])
                .map(f => ({
                    palabraClave: f[7].toLowerCase().trim(),
                    respuesta: f[8]
                }));

            const mensajeLowerCase = mensaje.toLowerCase();
            for (const item of primerasConversaciones) {
                if (mensajeLowerCase.includes(item.palabraClave)) {
                    return item.respuesta;
                }
            }
        }

        // Lógica normal
        const preguntasRespuestas = filas
            .filter(f => f[1] === idUsuario && f[2] && f[3])
            .map(f => ({
                pregunta: f[2].toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, ""),
                respuesta: f[3],
                requiereContexto: f[6] || null,
            }));

        if (preguntasRespuestas.length === 0) {
            return null;
        }

        const ultimoMensaje = ultimoMensajeBot.get(numeroRemitente);
        const preguntasNormalizadas = preguntasRespuestas.map(p => p.pregunta);
        const mensajeNormalizado = mensaje.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

        const match = stringSimilarity.findBestMatch(mensajeNormalizado, preguntasNormalizadas);
        const mejor = match.bestMatch;
        const index = preguntasNormalizadas.indexOf(mejor.target);
        const coincidencia = preguntasRespuestas[index];

        if (mejor.rating >= 0.75) {
            if (coincidencia.requiereContexto && (!ultimoMensaje || !ultimoMensaje.includes("¿Te gustaría conocer el proceso de compra?"))) {
                return null;
            }
            return coincidencia.respuesta;
        }

        return null;
    } catch (error) {
        console.error('❌ Error al procesar mensaje:', error);
        return null;
    }
}

async function enviarImagen(destinatario, nombreArchivo) {
    const rutaCompleta = path.join(__dirname, 'imagenes', nombreArchivo.trim());
    
    try {
        const media = MessageMedia.fromFilePath(rutaCompleta);
        await client.sendMessage(destinatario, media);
    } catch (error) {
        console.error('❌ Error al enviar imagen:', error);
    }
}

// Iniciar servidor
app.listen(port, () => {
    console.log(`🚀 Servidor iniciado en puerto ${port}`);
    console.log(`🌐 Accede a: http://localhost:${port}`);
});

// Auto-iniciar bot si está en producción
if (process.env.NODE_ENV === 'production') {
    setTimeout(iniciarBot, 5000);
}