# Dockerfile Corregido - Asegurar creación del directorio snippets
FROM php:8.2-apache

# Instalar extensiones PHP necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar módulos Apache necesarios
RUN a2enmod rewrite
RUN a2enmod headers

# Configurar Apache básico
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos PHP
COPY public/ /var/www/html/

# Copiar .htaccess
COPY .htaccess /var/www/html/.htaccess

# CREAR DIRECTORIO SNIPPETS CON PERMISOS CORRECTOS
RUN mkdir -p /var/www/html/snippets && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 777 /var/www/html/snippets && \
    echo "Directorio snippets creado correctamente"

# VERIFICAR QUE EL DIRECTORIO EXISTE
RUN ls -la /var/www/html/ && \
    ls -la /var/www/html/snippets/ || echo "Error: snippets no existe"

# CREAR ARCHIVO DE PRUEBA PARA VERIFICAR ESCRITURA
RUN echo "<?php echo 'Test file'; ?>" > /var/www/html/snippets/test.php && \
    chmod 777 /var/www/html/snippets/test.php

# Exponer puerto
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
