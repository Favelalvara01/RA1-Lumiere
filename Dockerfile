FROM php:8.2-apache

# Habilitar la extensión mysqli necesaria para conectar con MySQL
RUN docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli

# Habilitar mod_rewrite (útil si más adelante se agregan rutas amigables)
RUN a2enmod rewrite

# Copiar el código fuente del sitio al directorio público de Apache
COPY src/ /var/www/html/

# Permisos razonables para Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
