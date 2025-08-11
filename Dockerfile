# Dockerfile - PHP + Node.js para WhatsApp Bot
FROM php:8.2-apache

# Instalar Node.js 18
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

# Instalar dependencias del sistema para Puppeteer
RUN apt-get update && apt-get install -y \
    chromium \
    fonts-liberation \
    libasound2 \
    libatk-bridge2.0-0 \
    libatk1.0-0 \
    libatspi2.0-0 \
    libcups2 \
    libdbus-1-3 \
    libdrm2 \
    libgtk-3-0 \
    libnspr4 \
    libnss3 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxrandr2 \
    libxss1 \
    libxtst6 \
    xvfb

# Instalar extensiones PHP necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar módulos Apache necesarios
RUN a2enmod rewrite
RUN a2enmod headers

# Configurar Apache básico
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Configurar Puppeteer para usar Chromium instalado
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos PHP y tools
COPY public/ /var/www/html/

# Copiar .htaccess
COPY .htaccess /var/www/html/.htaccess

# Instalar dependencias de Node.js para WhatsApp Bot
WORKDIR /var/www/html/tools/whatsapp-bot
RUN npm install

# Volver al directorio principal
WORKDIR /var/www/html

# Dar permisos generales
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Crear directorio para sesiones de WhatsApp
RUN mkdir -p /var/www/html/tools/whatsapp-bot/whatsapp_sessions && \
    chown -R www-data:www-data /var/www/html/tools/whatsapp-bot/whatsapp_sessions

# Exponer puerto
EXPOSE 80

# Crear script de inicio que ejecute bot + Apache
RUN echo '#!/bin/bash\n\
cd /var/www/html/tools/whatsapp-bot\n\
echo "🚀 Iniciando WhatsApp Bot en background..."\n\
nohup node bot1.js > /var/log/whatsapp-bot.log 2>&1 &\n\
echo "📋 Bot iniciado, logs en /var/log/whatsapp-bot.log"\n\
echo "🌐 Iniciando Apache..."\n\
exec apache2-foreground' > /start-services.sh && \
chmod +x /start-services.sh

# Iniciar bot + Apache automáticamente
CMD ["/start-services.sh"]
