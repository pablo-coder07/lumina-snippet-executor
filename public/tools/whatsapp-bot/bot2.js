// -----------------------------------
// 🔁 INICIO: WHATSAPP WEB.JS - BOT 2
// -----------------------------------
const { Client, MessageMedia, LocalAuth } = require('whatsapp-web.js'); // 📸 Agregamos LocalAuth para persistencia
const qrcode = require('qrcode-terminal');
const moment = require('moment-timezone');
const path = require('path'); // 📁 Para manejar rutas de archivos
const fs = require('fs'); // 📂 Para verificar si existe el archivo

const client = new Client({
    authStrategy: new LocalAuth({
        clientId: 'bot2', // 🔑 ID único para bot2
        dataPath: './whatsapp_sessions_bot2' // 📁 Carpeta separada para bot2
    }),
    puppeteer: {
        headless: true,
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
    },
    webVersionCache: {
        type: 'remote',
        remotePath: 'https://raw.githubusercontent.com/wppconnect-team/wa-version/main/html/2.2412.54.html',
    }
});

let numeroBot = null;
const mensajesProcesados = new Set(); // 🛡️ Evitar respuestas duplicadas
const ultimoMensajeBot = new Map(); // 🔄 Guardar el último mensaje enviado por cada usuario
const mensajesPendientes = []; // 📋 Cola de mensajes pendientes para procesar a las 7 AM

// 📸 Ruta base para las imágenes - BOT 2
const RUTA_IMAGENES = path.join(__dirname, 'imagenes_bot2'); // 📁 Carpeta separada para imágenes del bot2

client.on('qr', (qr) => {
    console.log('🤖 BOT 2 - Escanea este código QR con WhatsApp:');
    qrcode.generate(qr, { small: true });
});

client.on('ready', () => {
    console.log(`✅ BOT 2 - Cliente de WhatsApp está listo! Esperando mensajes...`);
    
    // 🕰️ Iniciar el sistema de verificación de horarios cada minuto
    iniciarVerificadorHorarios();
});

client.on('message', async message => {
    const mensajeId = message.id._serialized;

    if (mensajesProcesados.has(mensajeId)) {
        console.log(`⚠️ BOT 2 - Mensaje ya procesado (ID: ${mensajeId}), se ignora.`);
        return;
    }

    // 🛡️ Marcar como procesado inmediatamente
    mensajesProcesados.add(mensajeId);
    
    // Agregar pequeño delay para evitar sobrecarga
    await new Promise(resolve => setTimeout(resolve, 800));

    const numeroRemitente = message.from;
    const texto = message.body?.trim();

    if (!numeroBot && message.to) {
        numeroBot = message.to;
        console.log(`📲 BOT 2 - Número del bot detectado automáticamente: ${numeroBot}`);
    }

    // Validaciones iniciales
    if (!texto || !numeroRemitente.endsWith('@c.us') || numeroRemitente.includes('status@')) {
        console.log('⚠️ BOT 2 - Mensaje no válido o no es de un usuario.');
        return;
    }

    // 🕒 Validar horario laboral colombiano
    const ahora = moment().tz('America/Bogota');
    const hora = ahora.hour();
    const minutos = ahora.minute();
    const horaTexto = ahora.format('h:mm A');
    const horaCompleta = hora + (minutos / 60); // Convertir a decimal para comparación precisa

    // Horario: 7:00 AM a 10:15 PM (22:15)
    const enHorarioLaboral = horaCompleta >= 7 && horaCompleta <= 22.25;

    if (!enHorarioLaboral) {
        console.log(`⏰ BOT 2 - Mensaje recibido fuera del horario laboral (${horaTexto}). Agregando a cola de pendientes.`);
        
        // Agregar mensaje a la cola de pendientes
        mensajesPendientes.push({
            numeroRemitente,
            texto,
            numeroBot,
            timestamp: ahora.format(),
            horaRecibido: horaTexto
        });
        
        console.log(`📋 BOT 2 - Mensajes pendientes en cola: ${mensajesPendientes.length}`);
        return;
    }

    // 🔍 Log detallado de mensaje entrante
    console.log('-------------------------------');
    console.log('📥 BOT 2 - NUEVO MENSAJE DETECTADO');
    console.log(`🕒 Hora: ${horaTexto}`);
    console.log(`👤 De: ${numeroRemitente}`);
    console.log(`📝 Texto: "${texto}"`);

    if (!numeroBot) {
        console.log('⚠️ BOT 2 - El número del bot aún no está disponible.');
        return;
    }

    const respuesta = await procesarMensaje(numeroBot, texto, numeroRemitente);

    if (respuesta) {
        console.log('✅ BOT 2 - Respuesta encontrada, se enviará al usuario.');
        
        // 📸 Verificar si la respuesta es una imagen
        if (respuesta.toLowerCase().includes('.jpeg') || respuesta.toLowerCase().includes('.jpg') || respuesta.toLowerCase().includes('.png')) {
            await enviarImagen(message.from, respuesta);
        } else {
            // Enviar mensaje de texto normal con validación mejorada
            await enviarMensajeTexto(message.from, respuesta);
        }
        
        // 🔄 Guardar el último mensaje enviado por el bot para este usuario
        ultimoMensajeBot.set(numeroRemitente, respuesta);
        console.log(`💾 BOT 2 - Guardado contexto para ${numeroRemitente}: "${respuesta.substring(0, 50)}..."`);
        
    } else {
        console.log('❌ BOT 2 - No se encontró una respuesta adecuada.');
    }

    console.log('-------------------------------');
});

// 💬 Función mejorada para enviar mensajes de texto
async function enviarMensajeTexto(destinatario, mensaje) {
    try {
        // Verificar que el cliente esté listo antes de enviar
        if (!client.info || !client.info.wid) {
            console.log('⚠️ BOT 2 - Cliente no está completamente inicializado, esperando...');
            await new Promise(resolve => setTimeout(resolve, 2000));
        }

        // Validar que el destinatario sea válido
        if (!destinatario || !destinatario.includes('@c.us')) {
            console.error('❌ BOT 2 - Destinatario inválido:', destinatario);
            return;
        }

        // Delay adicional para evitar problemas de sincronización
        await new Promise(resolve => setTimeout(resolve, 1200));
        
        await client.sendMessage(destinatario, mensaje);
        console.log('📤 BOT 2 - Mensaje enviado exitosamente.');
        
    } catch (error) {
        // Solo mostrar el error si es diferente al conocido problema de serialize
        if (!error.message.includes('serialize') && !error.message.includes('getMessageModel')) {
            console.error('❌ BOT 2 - Error al enviar mensaje:', error.message);
        } else {
            console.log('📤 BOT 2 - Mensaje procesado (ignorando error interno de whatsapp-web.js)');
        }
    }
}

// 📸 Función mejorada para enviar imágenes
async function enviarImagen(destinatario, nombreArchivo) {
    // Limpiar el nombre del archivo (remover espacios y caracteres especiales)
    const archivoLimpio = nombreArchivo.trim();
    const rutaCompleta = path.join(RUTA_IMAGENES, archivoLimpio);
    
    console.log(`📸 BOT 2 - Intentando enviar imagen: ${rutaCompleta}`);
    
    // Verificar si el archivo existe
    if (!fs.existsSync(rutaCompleta)) {
        console.error(`❌ BOT 2 - La imagen no existe en la ruta: ${rutaCompleta}`);
        return;
    }
    
    try {
        // Verificar que el cliente esté listo antes de enviar
        if (!client.info || !client.info.wid) {
            console.log('⚠️ BOT 2 - Cliente no está completamente inicializado, esperando...');
            await new Promise(resolve => setTimeout(resolve, 2000));
        }

        // Validar que el destinatario sea válido
        if (!destinatario || !destinatario.includes('@c.us')) {
            console.error('❌ BOT 2 - Destinatario inválido:', destinatario);
            return;
        }
        
        // Crear el media object para WhatsApp
        const media = MessageMedia.fromFilePath(rutaCompleta);
        
        // Delay adicional para evitar problemas de sincronización
        await new Promise(resolve => setTimeout(resolve, 1500));
        
        await client.sendMessage(destinatario, media);
        console.log('📤 BOT 2 - Imagen enviada exitosamente.');
        
    } catch (error) {
        // Solo mostrar el error si es diferente al conocido problema de serialize
        if (!error.message.includes('serialize') && !error.message.includes('getMessageModel')) {
            console.error('❌ BOT 2 - Error al enviar imagen:', error.message);
        } else {
            console.log('📤 BOT 2 - Imagen procesada (ignorando error interno de whatsapp-web.js)');
        }
    }
}

// 🕰️ Sistema de verificación de horarios y procesamiento de mensajes pendientes
function iniciarVerificadorHorarios() {
    console.log('🕰️ BOT 2 - Sistema de horarios iniciado. Verificando cada minuto...');
    
    setInterval(async () => {
        const ahora = moment().tz('America/Bogota');
        const hora = ahora.hour();
        const minutos = ahora.minute();
        
        // Verificar si son exactamente las 7:00 AM
        if (hora === 7 && minutos === 0) {
            console.log('🌅 BOT 2 - ¡Son las 7:00 AM! Procesando mensajes pendientes...');
            await procesarMensajesPendientes();
        }
        
        // Log cada hora para monitoreo (opcional, puedes comentar esta línea)
        if (minutos === 0) {
            console.log(`🕐 BOT 2 - Hora actual: ${ahora.format('h:mm A')} - Mensajes pendientes: ${mensajesPendientes.length}`);
        }
        
    }, 60000); // Verificar cada minuto
}

// 📋 Procesar todos los mensajes pendientes
async function procesarMensajesPendientes() {
    if (mensajesPendientes.length === 0) {
        console.log('📭 BOT 2 - No hay mensajes pendientes para procesar.');
        return;
    }
    
    console.log(`📬 BOT 2 - Procesando ${mensajesPendientes.length} mensajes pendientes...`);
    
    // Crear una copia de los mensajes pendientes y limpiar el array original
    const mensajesAProcesar = [...mensajesPendientes];
    mensajesPendientes.length = 0;
    
    for (let i = 0; i < mensajesAProcesar.length; i++) {
        const mensajePendiente = mensajesAProcesar[i];
        
        console.log(`📤 BOT 2 - Procesando mensaje ${i + 1}/${mensajesAProcesar.length}:`);
        console.log(`   👤 De: ${mensajePendiente.numeroRemitente}`);
        console.log(`   📝 Texto: "${mensajePendiente.texto}"`);
        console.log(`   🕒 Recibido: ${mensajePendiente.horaRecibido}`);
        
        try {
            // Procesar el mensaje como si fuera recibido ahora
            const respuesta = await procesarMensaje(
                mensajePendiente.numeroBot, 
                mensajePendiente.texto, 
                mensajePendiente.numeroRemitente
            );
            
            if (respuesta) {
                // 📸 Verificar si la respuesta es una imagen
                if (respuesta.toLowerCase().includes('.jpeg') || respuesta.toLowerCase().includes('.jpg') || respuesta.toLowerCase().includes('.png')) {
                    await enviarImagen(mensajePendiente.numeroRemitente, respuesta);
                } else {
                    await enviarMensajeTexto(mensajePendiente.numeroRemitente, respuesta);
                }
                
                // Guardar contexto
                ultimoMensajeBot.set(mensajePendiente.numeroRemitente, respuesta);
                
                console.log(`   ✅ BOT 2 - Mensaje pendiente procesado exitosamente.`);
            } else {
                console.log(`   ❌ BOT 2 - No se encontró respuesta para el mensaje pendiente.`);
            }
            
            // Delay entre mensajes para evitar spam
            if (i < mensajesAProcesar.length - 1) {
                await new Promise(resolve => setTimeout(resolve, 2000));
            }
            
        } catch (error) {
            console.error(`   ❌ BOT 2 - Error al procesar mensaje pendiente:`, error.message);
        }
    }
    
    console.log(`🎉 BOT 2 - Finalizado el procesamiento de mensajes pendientes.`);
}

// 🔧 Manejo de errores no capturados
process.on('unhandledRejection', (reason, promise) => {
    if (reason && reason.message && reason.message.includes('serialize')) {
        // Ignorar errores conocidos de serialize
        return;
    }
    console.error('BOT 2 - Unhandled Rejection at:', promise, 'reason:', reason);
});

client.initialize();

// -----------------------------------
// 🧠 INICIO: PROCESADOR CON SHEETS - BOT 2
// -----------------------------------
const { google } = require('googleapis');
const stringSimilarity = require('string-similarity');
const SHEET_ID = '1Sik4rqd1u7c8heQ6aA-TmNXw1UUiIOIOpSdqZC9OGrE'; // 📊 MISMO Google Sheet que bot1
const SHEET_NAME = 'Sheet1';

const auth = new google.auth.GoogleAuth({
    keyFile: './credentials.json',
    scopes: ['https://www.googleapis.com/auth/spreadsheets.readonly'],
});

const sheets = google.sheets({ version: 'v4', auth });

async function procesarMensaje(numeroBot, mensaje, numeroRemitente) {
    try {
        const datos = await sheets.spreadsheets.values.get({
            spreadsheetId: SHEET_ID,
            range: `${SHEET_NAME}!A2:G`, // 📋 Extendemos el rango para incluir columna G
        });

        const filas = datos.data.values || [];
        const numeroLimpio = numeroBot.replace('@c.us', '');

        const filaCoincidente = filas.find(f => f[5] && f[5].includes(numeroLimpio));
        if (!filaCoincidente) {
            console.log(`⚠️ BOT 2 - No se encontró ningún ID vinculado al número: ${numeroLimpio}`);
            return null;
        }

        const idUsuario = filaCoincidente[1];
        console.log(`👤 BOT 2 - ID detectado para el número ${numeroLimpio}: ${idUsuario}`);

        const preguntasRespuestas = filas
            .filter(f => f[1] === idUsuario && f[2] && f[3])
            .map(f => ({
                pregunta: f[2].toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, ""),
                respuesta: f[3],
                original: f[2],
                requiereContexto: f[6] || null, // 🔄 Columna G para contexto
            }));

        if (preguntasRespuestas.length === 0) {
            console.log(`⚠️ BOT 2 - No se encontraron preguntas configuradas para el ID: ${idUsuario}`);
            return null;
        }

        // 🔄 Obtener el último mensaje enviado por el bot a este usuario
        const ultimoMensaje = ultimoMensajeBot.get(numeroRemitente);
        console.log(`🔍 BOT 2 - Último mensaje del bot para este usuario: "${ultimoMensaje ? ultimoMensaje.substring(0, 50) + '...' : 'Ninguno'}"`);

        const preguntasNormalizadas = preguntasRespuestas.map(p => p.pregunta);
        const mensajeNormalizado = mensaje.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

        const match = stringSimilarity.findBestMatch(mensajeNormalizado, preguntasNormalizadas);
        const mejor = match.bestMatch;
        const index = preguntasNormalizadas.indexOf(mejor.target);
        const coincidencia = preguntasRespuestas[index];

        console.log(`🔍 BOT 2 - Pregunta con mejor coincidencia: "${coincidencia.original}" (confianza: ${mejor.rating})`);

        if (mejor.rating >= 0.75) {
            // 🔄 Verificar si esta respuesta requiere contexto específico
            if (coincidencia.requiereContexto) {
                console.log(`🔄 BOT 2 - Esta respuesta requiere contexto: "${coincidencia.requiereContexto}"`);
                
                // Verificar si el último mensaje del bot contenía la frase requerida
                if (!ultimoMensaje) {
                    console.log(`❌ BOT 2 - No hay contexto previo. Respuesta contextual ignorada.`);
                    return null;
                }
                
                // Verificar específicamente si termina con "¿Te gustaría conocer el proceso de compra?"
                if (!ultimoMensaje.includes("¿Te gustaría conocer el proceso de compra?")) {
                    console.log(`❌ BOT 2 - El último mensaje no terminaba con la frase requerida. Respuesta contextual ignorada.`);
                    return null;
                }
                
                console.log(`✅ BOT 2 - Contexto válido encontrado. Enviando respuesta contextual.`);
            }
            
            return coincidencia.respuesta;
        }

        console.log(`🤷‍♂️ BOT 2 - Ninguna coincidencia suficiente (>= 0.75) para el mensaje: "${mensaje}"`);
        return null;

    } catch (error) {
        console.error('❌ BOT 2 - Error al procesar el mensaje:', error);
        return null;
    }
}