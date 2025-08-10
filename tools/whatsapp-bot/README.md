# 🤖 WhatsApp Bot con Google Sheets

Un bot de WhatsApp automatizado que responde mensajes basándose en configuraciones almacenadas en Google Sheets, con soporte para imágenes, horarios laborales, y respuestas contextuales.

## ✨ Características

- 📊 **Configuración vía Google Sheets**: Todas las preguntas y respuestas se configuran desde Excel/Google Sheets
- 🖼️ **Envío de imágenes**: Soporte para archivos JPEG, JPG, PNG
- 🕐 **Horarios laborales**: Respuestas automáticas solo en horarios configurados (7:00 AM - 10:15 PM)
- 📋 **Cola de mensajes pendientes**: Mensajes fuera de horario se procesan a las 7:00 AM
- 🔄 **Respuestas contextuales**: Sistema de respuestas que requieren mensajes previos específicos
- 🆕 **Primera conversación**: Lógica especial para nuevos usuarios con palabras clave
- 🧹 **Limpieza automática**: Eliminación de procesos zombie al iniciar/cerrar
- 🛡️ **Control de duplicados**: Evita respuestas múltiples al mismo mensaje

## 📋 Requisitos

- Node.js v16 o superior
- NPM
- Cuenta de WhatsApp
- Google Sheets API habilitada
- Credenciales de Google Service Account

## 🚀 Instalación

### 1. Clonar o descargar el proyecto
```bash
mkdir mi-backend
cd mi-backend
```

### 2. Instalar dependencias
```bash
npm install whatsapp-web.js qrcode-terminal moment-timezone googleapis string-similarity
```

### 3. Configurar Google Sheets API

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Habilita la API de Google Sheets
4. Crea credenciales de Service Account
5. Descarga el archivo JSON y nómbralo `credentials.json`
6. Coloca `credentials.json` en la carpeta del proyecto

### 4. Estructura de archivos
```
mi-backend/
├── bot1.js
├── bot2.js (opcional)
├── credentials.json
├── imagenes/
│   ├── imagen1.jpeg
│   └── imagen2.png
├── package.json
└── whatsapp_sessions/ (se crea automáticamente)
```

## 📊 Configuración de Google Sheets

### 🔗 Crear y configurar Google Sheets

#### 1. Crear el Google Sheet:
1. Ve a [Google Sheets](https://sheets.google.com/)
2. Crea una nueva hoja de cálculo
3. Nómbrala: "WhatsApp Bot Responses" (o como prefieras)
4. Copia la **URL completa**, ejemplo:
   ```
   https://docs.google.com/spreadsheets/d/1Sik4rqd1u7c8heQ6aA-TmNXw1UUiIOIOpSdqZC9OGrE/edit
   ```

#### 2. Obtener el SHEET_ID:
De la URL anterior, copia solo la parte del ID:
```
1Sik4rqd1u7c8heQ6aA-TmNXw1UUiIOIOpSdqZC9OGrE
```

#### 3. Configurar en el código:
```javascript
const SHEET_ID = '1Sik4rqd1u7c8heQ6aA-TmNXw1UUiIOIOpSdqZC9OGrE'; // Tu ID aquí
const SHEET_NAME = 'Sheet1'; // Nombre de la pestaña (por defecto 'Sheet1')
```

#### 4. Compartir el Google Sheet:
1. En tu Google Sheet, clic en **"Compartir"**
2. Agregar el email del Service Account (está en tu `credentials.json`):
   ```json
   "client_email": "tu-service-account@proyecto.iam.gserviceaccount.com"
   ```
3. Dar permisos de **"Editor"**
4. Enviar invitación

### Estructura de columnas:

| Columna | Propósito | Ejemplo |
|---------|-----------|---------|
| **A** | Datos internos | - |
| **B** | ID de usuario | 264 |
| **C** | Pregunta del usuario | "hola" |
| **D** | Respuesta del bot | "¡Hola! ¿En qué puedo ayudarte?" |
| **E** | Datos internos | - |
| **F** | Número de teléfono del bot | 573196586891 |
| **G** | Control contextual | (vacío = inmediato, texto = contextual) |
| **H** | Palabras clave primera conversación | "precio" |
| **I** | Respuesta primera conversación | "¡Hola! Nuestros precios..." |

### Ejemplo de configuración completa:

#### Configuración para usuario ID 264:
| A | B | C | D | E | F | G | H | I |
|---|---|---|---|---|---|---|---|---|
| 1 | **264** | "hola" | "¡Hola! ¿En qué puedo ayudarte?" | - | **573196586891** | - | "precio" | "Nuestros precios inician desde $50,000" |
| 2 | **264** | "camiseta" | "Tenemos camisetas personalizadas. ¿Te gustaría conocer el proceso de compra?" | - | **573196586891** | - | "cotizar" | "¡Bienvenido! Te ayudo a cotizar tu camiseta..." |
| 3 | **264** | "si" | "Instrucciones.jpeg" | - | **573196586891** | **contextual** | - | - |
| 4 | **264** | "no" | "Está bien, ¿en qué más puedo ayudarte?" | - | **573196586891** | **contextual** | - | - |

#### ⚠️ Puntos importantes:
- **Columna B**: Debe ser el mismo ID para todas las filas del mismo usuario
- **Columna F**: Debe contener el número de WhatsApp del bot (sin @c.us)
- **Columna G**: Déjala vacía para respuestas inmediatas, pon cualquier texto para respuestas contextuales
- **Columnas H e I**: Solo para primera conversación, pueden estar vacías si no las usas

#### 📝 Configurar múltiples usuarios:
Para agregar un segundo bot (usuario 265):
| A | B | C | D | E | F | G | H | I |
|---|---|---|---|---|---|---|---|---|
| 5 | **265** | "hola" | "¡Hola desde el bot 2!" | - | **573987654321** | - | "info" | "¡Bienvenido al bot 2!" |

### 🔄 Nombre de la pestaña:
- Por defecto: **"Sheet1"**
- Si cambias el nombre, actualiza en el código:
  ```javascript
  const SHEET_NAME = 'TuNombreDePestana';
  ```

## 🎮 Uso

### Iniciar el bot:
```bash
cd Documents/mi-backend
node bot1.js
```

### Proceso de vinculación:
1. Ejecutar el comando anterior
2. Escanear el código QR con WhatsApp
3. ¡El bot está listo para recibir mensajes!

### Detener el bot:
```
Ctrl + C
```

## 🔧 Configuración avanzada

### Horarios laborales
Modificar en el código:
```javascript
// Horario: 7:00 AM a 10:15 PM (22:15)
const enHorarioLaboral = horaCompleta >= 7 && horaCompleta <= 22.25;
```

### ID de Google Sheets
1. **Obtener ID del Google Sheet**:
   - De la URL: `https://docs.google.com/spreadsheets/d/ESTE_ES_EL_ID/edit`
   - Copiar solo la parte del ID

2. **Configurar en el código**:
```javascript
const SHEET_ID = 'TU_ID_DE_GOOGLE_SHEETS_AQUI'; // ⚠️ CAMBIAR ESTE ID
const SHEET_NAME = 'Sheet1'; // Nombre de la pestaña
```

3. **Ejemplo real**:
```javascript
const SHEET_ID = '1Sik4rqd1u7c8heQ6aA-TmNXw1UUiIOIOpSdqZC9OGrE';
```

### Carpeta de imágenes
```javascript
const RUTA_IMAGENES = path.join(__dirname, 'imagenes');
```

## 📱 Tipos de respuestas

### 1. Respuesta de texto normal
- **Columna C**: Pregunta del usuario
- **Columna D**: Respuesta del bot
- **Columna G**: Vacía

### 2. Respuesta con imagen
- **Columna D**: Nombre del archivo (ej: "imagen.jpeg")
- El archivo debe estar en la carpeta `imagenes/`

### 3. Respuesta contextual
- **Columna G**: Cualquier texto (ej: "contextual")
- Solo se activa después de mensajes que terminen con "¿Te gustaría conocer el proceso de compra?"

### 4. Primera conversación
- **Columna H**: Palabra clave (ej: "precio")
- **Columna I**: Respuesta especial
- Se activa solo en el primer mensaje del usuario
- 100% de coincidencia si contiene la palabra clave

## 🛠️ Funciones principales

### Sistema de similitud
- Utiliza `string-similarity` para encontrar coincidencias
- Umbral mínimo: 75% de similitud
- Normaliza texto (acentos, mayúsculas/minúsculas)

### Control de contexto
- Guarda el último mensaje enviado por el bot
- Valida condiciones específicas para respuestas contextuales

### Primera conversación
- Detecta usuarios nuevos por sesión
- Busca palabras clave exactas en el mensaje
- Prioridad sobre respuestas normales

## 🚨 Solución de problemas

### El bot no genera QR
```bash
# Limpiar procesos
sudo pkill -f Chromium
sudo pkill -f whatsapp_sessions
rm -rf whatsapp_sessions

# Reintentar
node bot1.js
```

### Error de Google Sheets
- Verificar que `credentials.json` esté en la carpeta correcta
- Confirmar que la API de Google Sheets esté habilitada
- Revisar permisos del Service Account

### Imágenes no se envían
- Verificar que el archivo existe en la carpeta `imagenes/`
- Confirmar que el nombre en el Excel coincide exactamente
- Soporta: .jpeg, .jpg, .png

## 📊 Logs del sistema

El bot muestra información detallada:
- `🆕 PRIMERA CONVERSACIÓN detectada`
- `🎯 PALABRA CLAVE ENCONTRADA`
- `📸 Intentando enviar imagen`
- `🔄 Esta respuesta requiere contexto`
- `⏰ Mensaje recibido fuera del horario laboral`

## 🔄 Bot múltiple

Para ejecutar múltiples bots:

1. Usar `bot1.js` y `bot2.js`
2. Configurar diferentes `clientId`
3. Ejecutar en terminales separadas:
```bash
# Terminal 1
node bot1.js

# Terminal 2
node bot2.js
```

## 📝 Estructura del proyecto

```
mi-backend/
├── bot1.js                 # Bot principal
├── bot2.js                 # Bot secundario (opcional)
├── credentials.json        # Credenciales de Google
├── package.json           # Dependencias
├── imagenes/              # Archivos multimedia
│   ├── imagen1.jpeg
│   └── imagen2.png
└── whatsapp_sessions/     # Sesiones de WhatsApp (auto-generado)
    └── session-bot1/
```

## 🤝 Contribuir

1. Fork del proyecto
2. Crear rama para nueva característica
3. Commit de cambios
4. Push a la rama
5. Abrir Pull Request

## 📄 Licencia

Este proyecto es de código abierto y está disponible bajo la licencia MIT.

## 🔗 Dependencias principales

- [whatsapp-web.js](https://github.com/pedroslopez/whatsapp-web.js) - Cliente de WhatsApp
- [googleapis](https://github.com/googleapis/google-api-nodejs-client) - API de Google Sheets
- [string-similarity](https://github.com/aceakash/string-similarity) - Comparación de strings
- [moment-timezone](https://momentjs.com/timezone/) - Manejo de fechas y horarios
- [qrcode-terminal](https://github.com/gtanner/qrcode-terminal) - Generación de QR en terminal

## 📞 Soporte

Para reportar bugs o solicitar características, crear un issue en el repositorio del proyecto.

---

**¡Tu bot de WhatsApp está listo para automatizar conversaciones! 🚀**