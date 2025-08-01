# Dockerfile Simple - Sin pre-crear directorio
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

# Dar permisos generales (el directorio snippets se creará automáticamente)
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Exponer puerto
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
