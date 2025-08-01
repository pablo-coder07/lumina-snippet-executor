# Dockerfile Final - Funcional para Render
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

# Copiar .htaccess (ahora funcionará)
COPY .htaccess /var/www/html/.htaccess

# Crear directorio snippets y permisos
RUN mkdir -p /var/www/html/snippets && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod 777 /var/www/html/snippets

# Exponer puerto
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
