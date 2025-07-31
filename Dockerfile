# Dockerfile para Render - Lumina Snippet Executor
FROM php:8.2-apache

# Instalar extensiones PHP necesarias
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Configurar Apache para Render
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# Crear directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de la aplicación
COPY public/ /var/www/html/

# Crear directorio snippets con permisos correctos
RUN mkdir -p /var/www/html/snippets && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod 777 /var/www/html/snippets

# Configurar Apache para producción
COPY .htaccess /var/www/html/.htaccess
RUN chown www-data:www-data /var/www/html/.htaccess

# Exponer puerto para Render
EXPOSE 80

# Comando de inicio
CMD ["apache2-foreground"]
