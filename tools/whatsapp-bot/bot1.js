// -----------------------------------
// 🔁 INICIO: WHATSAPP WEB.JS
// -----------------------------------
const { Client, MessageMedia, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const moment = require('moment-timezone');
const path = require('path');
const fs = require('fs');

// 🧹 Limpieza inicial de procesos zombie
console.log('🧹 Limpiando procesos previos...');
try {
    const { execSync } = require('child_process');
    execSync('pkill -f whatsapp_sessions || true', { stdio: 'ignore' });
    execSync('rm -rf whatsapp_sessions || true', { stdio: 'ignore' });
    console.log('🧹 Limpieza completada.');
} catch (error) {
    console.log('ℹ️ Limpieza inicial terminada.');
}

const client = new Client({
    authStrategy: new LocalAuth({
        clientId: 'bot1',
        dataPath: './whatsapp_sessions'
    }),
    puppeteer: {
        headless: true,
        timeout: 60000, // 🕐 Aumentar timeout a 60 segundos
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
            '--disable-plugins',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding',
            '--disable-features=TranslateUI',
            '--disable-ipc-flooding-protection',
            '--force-color-profile=srgb'
        ]
    }
    // Removemos webVersionCache que puede causar problemas
});

let numeroBot = null;
const mensajesProcesados = new Set();
const ultimoMensajeBot = new Map();
const mensajesPendientes = [];
const historialUsuarios = new Map(); // 📋 Nuevo: Para rastrear si es primera conversación

// 📸 Ruta base para las imágenes
const RUTA_IMAGENES = path.join(__dirname, 'imagenes');

client.on('qr', (qr) => {
    console.log('🎯 ¡QR GENERADO! Escanea este código QR con WhatsApp:');
    qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
    console.log(`✅ Cliente de WhatsApp está listo! Esperando mensajes...`);
    iniciarVerificadorHorarios();
});

client.on('loading_screen', (percent, message) => {
    console.log(`⏳ Cargando: ${percent}% - ${message}`);
});

client.on('authenticated', () => {
    console.log('🔐 Cliente autenticado exitosamente');
});

client.on('auth_failure', msg => {
    console.error('❌ Error de autenticación:', msg);
    console.log('🧹 Limpiando sesiones corruptas...');
    try {
        const { execSync } = require('child_process');
        execSync('rm -rf whatsapp_sessions', { stdio: 'ignore' });
        console.log('✅ Sesiones limpiadas. Reinicia el bot.');
    } catch (error) {
        console.error('❌ Error al limpiar sesiones:', error.message);
    }
    process.exit(1);
});

client.on('disconnected', (reason) => {
    console.log('🚨 Cliente desconectado:', reason);
    console.log('ℹ️ Reinicia el bot manualmente: node bot1.js');
    process.exit(0);
});

client.on('message', async message => {
    const mensajeId = message.id._serialized;

    if (mensajesProcesados.has(mensajeId)) {
        console.log(`⚠️ Mensaje ya procesado (ID: ${mensajeId}), se ignora.`);
        return;
    }

    mensajesProcesados.add(mensajeId);
    await new Promise(resolve => setTimeout(resolve, 800));

    const numeroRemitente = message.from;
    const texto = message.body?.trim();

    if (!numeroBot && message.to) {
        numeroBot = message.to;
        console.log(`📲 Número del bot detectado automáticamente: ${numeroBot}`);
    }

    if (!texto || !numeroRemitente.endsWith('@c.us') || numeroRemitente.includes('status@')) {
        console.log('⚠️ Mensaje no válido o no es de un usuario.');
        return;
    }

    const ahora = moment().tz('America/Bogota');
    const hora = ahora.hour();
    const minutos = ahora.minute();
    const horaTexto = ahora.format('h:mm A');
    const horaCompleta = hora + (minutos / 60);

    const enHorarioLaboral = horaCompleta >= 7 && horaCompleta <= 22.25;

    if (!enHorarioLaboral) {
        console.log(`⏰ Mensaje recibido fuera del horario laboral (${horaTexto}). Agregando a cola de pendientes.`);
        
        mensajesPendientes.push({
            numeroRemitente,
            texto,
            numeroBot,
            timestamp: ahora.format(),
            horaRecibido: horaTexto
        });
        
        console.log(`📋 Mensajes pendientes en cola: ${mensajesPendientes.length}`);
        return;
    }

    console.log('-------------------------------');
    console.log('📥 NUEVO MENSAJE DETECTADO');
    console.log(`🕒 Hora: ${horaTexto}`);
    console.log(`👤 De: ${numeroRemitente}`);
    console.log(`📝 Texto: "${texto}"`);

    if (!numeroBot) {
        console.log('⚠️ El número del bot aún no está disponible.');
        return;
    }

        // 🔍 Verificar si es primera conversación
        const esPrimeraConversacion = !historialUsuarios.has(numeroRemitente);
        if (esPrimeraConversacion) {
            console.log('🆕 PRIMERA CONVERSACIÓN detectada para este usuario');
            historialUsuarios.set(numeroRemitente, true); // Marcar como usuario conocido
        }

        const respuesta = await procesarMensaje(numeroBot, texto, numeroRemitente, esPrimeraConversacion);

    if (respuesta) {
        console.log('✅ Respuesta encontrada, se enviará al usuario.');
        
        if (respuesta.toLowerCase().includes('.jpeg') || respuesta.toLowerCase().includes('.jpg') || respuesta.toLowerCase().includes('.png')) {
            await enviarImagen(message.from, respuesta);
        } else {
            await enviarMensajeTexto(message.from, respuesta);
        }
        
        ultimoMensajeBot.set(numeroRemitente, respuesta);
        console.log(`💾 Guardado contexto para ${numeroRemitente}: "${respuesta.substring(0, 50)}..."`);
        
    } else {
        console.log('❌ No se encontró una respuesta adecuada.');
    }

    console.log('-------------------------------');
});

async function enviarMensajeTexto(destinatario, mensaje) {
    try {
        if (!client.info || !client.info.wid) {
            console.log('⚠️ Cliente no está completamente inicializado, esperando...');
            await new Promise(resolve => setTimeout(resolve, 2000));
        }

        if (!destinatario || !destinatario.includes('@c.us')) {
            console.error('❌ Destinatario inválido:', destinatario);
            return;
        }

        await new Promise(resolve => setTimeout(resolve, 1200));
        
        await client.sendMessage(destinatario, mensaje);
        console.log('📤 Mensaje enviado exitosamente.');
        
    } catch (error) {
        if (!error.message.includes('serialize') && !error.message.includes('getMessageModel')) {
            console.error('❌ Error al enviar mensaje:', error.message);
        } else {
            console.log('📤 Mensaje procesado (ignorando error interno de whatsapp-web.js)');
        }
    }
}

async function enviarImagen(destinatario, nombreArchivo) {
    const archivoLimpio = nombreArchivo.trim();
    const rutaCompleta = path.join(RUTA_IMAGENES, archivoLimpio);
    
    console.log(`📸 Intentando enviar imagen: ${rutaCompleta}`);
    
    if (!fs.existsSync(rutaCompleta)) {
        console.error(`❌ La imagen no existe en la ruta: ${rutaCompleta}`);
        return;
    }
    
    try {
        if (!client.info || !client.info.wid) {
            console.log('⚠️ Cliente no está completamente inicializado, esperando...');
            await new Promise(resolve => setTimeout(resolve, 2000));
        }

        if (!destinatario || !destinatario.includes('@c.us')) {
            console.error('❌ Destinatario inválido:', destinatario);
            return;
        }
        
        const media = MessageMedia.fromFilePath(rutaCompleta);
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        await client.sendMessage(destinatario, media);
        console.log('📤 Imagen enviada exitosamente.');
        
    } catch (error) {
        if (!error.message.includes('serialize') && !error.message.includes('getMessageModel')) {
            console.error('❌ Error al enviar imagen:', error.message);
        } else {
            console.log('📤 Imagen procesada (ignorando error interno de whatsapp-web.js)');
        }
    }
}

function iniciarVerificadorHorarios() {
    console.log('🕰️ Sistema de horarios iniciado. Verificando cada minuto...');
    
    setInterval(async () => {
        const ahora = moment().tz('America/Bogota');
        const hora = ahora.hour();
        const minutos = ahora.minute();
        
        if (hora === 7 && minutos === 0) {
            console.log('🌅 ¡Son las 7:00 AM! Procesando mensajes pendientes...');
            await procesarMensajesPendientes();
        }
        
        if (minutos === 0) {
            console.log(`🕐 Hora actual: ${ahora.format('h:mm A')} - Mensajes pendientes: ${mensajesPendientes.length}`);
        }
        
    }, 60000);
}

async function procesarMensajesPendientes() {
    if (mensajesPendientes.length === 0) {
        console.log('📭 No hay mensajes pendientes para procesar.');
        return;
    }
    
    console.log(`📬 Procesando ${mensajesPendientes.length} mensajes pendientes...`);
    
    const mensajesAProcesar = [...mensajesPendientes];
    mensajesPendientes.length = 0;
    
    for (let i = 0; i < mensajesAProcesar.length; i++) {
        const mensajePendiente = mensajesAProcesar[i];
        
        console.log(`📤 Procesando mensaje ${i + 1}/${mensajesAProcesar.length}:`);
        console.log(`   👤 De: ${mensajePendiente.numeroRemitente}`);
        console.log(`   📝 Texto: "${mensajePendiente.texto}"`);
        console.log(`   🕒 Recibido: ${mensajePendiente.horaRecibido}`);
        
        try {
            const respuesta = await procesarMensaje(
                mensajePendiente.numeroBot, 
                mensajePendiente.texto, 
                mensajePendiente.numeroRemitente,
                false // Los mensajes pendientes no son primera conversación
            );
            
            if (respuesta) {
                if (respuesta.toLowerCase().includes('.jpeg') || respuesta.toLowerCase().includes('.jpg') || respuesta.toLowerCase().includes('.png')) {
                    await enviarImagen(mensajePendiente.numeroRemitente, respuesta);
                } else {
                    await enviarMensajeTexto(mensajePendiente.numeroRemitente, respuesta);
                }
                
                ultimoMensajeBot.set(mensajePendiente.numeroRemitente, respuesta);
                console.log(`   ✅ Mensaje pendiente procesado exitosamente.`);
            } else {
                console.log(`   ❌ No se encontró respuesta para el mensaje pendiente.`);
            }
            
            if (i < mensajesAProcesar.length - 1) {
                await new Promise(resolve => setTimeout(resolve, 2000));
            }
            
        } catch (error) {
            console.error(`   ❌ Error al procesar mensaje pendiente:`, error.message);
        }
    }
    
    console.log(`🎉 Finalizado el procesamiento de mensajes pendientes.`);
}

// 🔧 Manejo de errores más silencioso
process.on('unhandledRejection', (reason, promise) => {
    if (reason && reason.message && (reason.message.includes('serialize') || reason.message.includes('timeout'))) {
        return; // Ignorar errores conocidos
    }
    console.error('❌ Error no manejado:', reason.message);
});

// 🚨 Limpieza al cerrar
process.on('SIGINT', () => {
    console.log('\n🚨 Cerrando bot...');
    try {
        const { execSync } = require('child_process');
        execSync('pkill -f whatsapp_sessions || true', { stdio: 'ignore' });
    } catch (error) {
        // Ignorar errores de limpieza
    }
    process.exit(0);
});

console.log('🚀 Iniciando WhatsApp Bot...');
client.initialize();

// -----------------------------------
// 🧠 INICIO: PROCESADOR CON SHEETS
// -----------------------------------
const { google } = require('googleapis');
const stringSimilarity = require('string-similarity');
const SHEET_ID = '1Sik4rqd1u7c8heQ6aA-TmNXw1UUiIOIOpSdqZC9OGrE';
const SHEET_NAME = 'Sheet1';

const auth = new google.auth.GoogleAuth({
    keyFile: './credentials.json',
    scopes: ['https://www.googleapis.com/auth/spreadsheets.readonly'],
});

const sheets = google.sheets({ version: 'v4', auth });

async function procesarMensaje(numeroBot, mensaje, numeroRemitente, esPrimeraConversacion = false) {
    try {
        const datos = await sheets.spreadsheets.values.get({
            spreadsheetId: SHEET_ID,
            range: `${SHEET_NAME}!A2:I`, // 📋 Extendemos el rango para incluir columnas H e I
        });

        const filas = datos.data.values || [];
        const numeroLimpio = numeroBot.replace('@c.us', '');

        const filaCoincidente = filas.find(f => f[5] && f[5].includes(numeroLimpio));
        if (!filaCoincidente) {
            console.log(`⚠️ No se encontró ningún ID vinculado al número: ${numeroLimpio}`);
            return null;
        }

        const idUsuario = filaCoincidente[1];
        console.log(`👤 ID detectado para el número ${numeroLimpio}: ${idUsuario}`);

        // 🆕 LÓGICA ESPECIAL PARA PRIMERA CONVERSACIÓN
        if (esPrimeraConversacion) {
            console.log('🔍 Procesando como PRIMERA CONVERSACIÓN...');
            
            // Buscar palabras clave en columna H para primera conversación
            const primerasConversaciones = filas
                .filter(f => f[1] === idUsuario && f[7] && f[8]) // Columna H (índice 7) y I (índice 8)
                .map(f => ({
                    palabraClave: f[7].toLowerCase().trim(),
                    respuesta: f[8],
                    original: f[7]
                }));

            if (primerasConversaciones.length > 0) {
                console.log(`🔍 Encontradas ${primerasConversaciones.length} palabras clave para primera conversación`);
                
                const mensajeLowerCase = mensaje.toLowerCase();
                
                // Buscar si alguna palabra clave está contenida en el mensaje
                for (const item of primerasConversaciones) {
                    if (mensajeLowerCase.includes(item.palabraClave)) {
                        console.log(`🎯 PALABRA CLAVE ENCONTRADA en primera conversación: "${item.original}"`);
                        console.log(`✅ Enviando respuesta de primera conversación`);
                        return item.respuesta;
                    }
                }
                
                console.log(`❌ Ninguna palabra clave de primera conversación encontrada en: "${mensaje}"`);
            } else {
                console.log(`ℹ️ No hay palabras clave configuradas para primera conversación del usuario ${idUsuario}`);
            }
        }

        // 🔄 LÓGICA NORMAL PARA CONVERSACIONES REGULARES (Columnas C y D)
        console.log('🔍 Procesando como conversación regular...');
        
        const preguntasRespuestas = filas
            .filter(f => f[1] === idUsuario && f[2] && f[3])
            .map(f => ({
                pregunta: f[2].toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, ""),
                respuesta: f[3],
                original: f[2],
                requiereContexto: f[6] || null,
            }));

        if (preguntasRespuestas.length === 0) {
            console.log(`⚠️ No se encontraron preguntas configuradas para el ID: ${idUsuario}`);
            return null;
        }

        const ultimoMensaje = ultimoMensajeBot.get(numeroRemitente);
        console.log(`🔍 Último mensaje del bot para este usuario: "${ultimoMensaje ? ultimoMensaje.substring(0, 50) + '...' : 'Ninguno'}"`);

        const preguntasNormalizadas = preguntasRespuestas.map(p => p.pregunta);
        const mensajeNormalizado = mensaje.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

        const match = stringSimilarity.findBestMatch(mensajeNormalizado, preguntasNormalizadas);
        const mejor = match.bestMatch;
        const index = preguntasNormalizadas.indexOf(mejor.target);
        const coincidencia = preguntasRespuestas[index];

        console.log(`🔍 Pregunta con mejor coincidencia: "${coincidencia.original}" (confianza: ${mejor.rating})`);

        if (mejor.rating >= 0.75) {
            if (coincidencia.requiereContexto) {
                console.log(`🔄 Esta respuesta requiere contexto: "${coincidencia.requiereContexto}"`);
                
                if (!ultimoMensaje) {
                    console.log(`❌ No hay contexto previo. Respuesta contextual ignorada.`);
                    return null;
                }
                
                if (!ultimoMensaje.includes("¿Te gustaría conocer el proceso de compra?")) {
                    console.log(`❌ El último mensaje no terminaba con la frase requerida. Respuesta contextual ignorada.`);
                    return null;
                }
                
                console.log(`✅ Contexto válido encontrado. Enviando respuesta contextual.`);
            }
            
            return coincidencia.respuesta;
        }

        console.log(`🤷‍♂️ Ninguna coincidencia suficiente (>= 0.75) para el mensaje: "${mensaje}"`);
        return null;

    } catch (error) {
        console.error('❌ Error al procesar el mensaje:', error);
        return null;
    }
}