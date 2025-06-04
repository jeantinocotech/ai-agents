# Etapa 1: build frontend com Vite
FROM node:18-alpine as frontend

WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Etapa 2: PHP com Apache
FROM php:8.2-apache

# Instala dependências do sistema e extensões PHP
RUN apt-get update \
    && apt-get install -y unzip curl libzip-dev zip \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Define a raiz como /app/public
ENV APACHE_DOCUMENT_ROOT /app/public

# Atualiza o VirtualHost para refletir a nova raiz
RUN sed -i 's|/var/www/html|/app/public|g' /etc/apache2/sites-available/000-default.conf

# 🔥 Permitir acesso via .htaccess no novo diretório
RUN echo '<Directory "/app/public">' >> /etc/apache2/apache2.conf \
    && echo '    AllowOverride All' >> /etc/apache2/apache2.conf \
    && echo '    Require all granted' >> /etc/apache2/apache2.conf \
    && echo '</Directory>' >> /etc/apache2/apache2.conf

WORKDIR /app

# Copia tudo do frontend (Laravel + build Vite)
COPY --from=frontend /app /app

# Instala dependências PHP
RUN composer install --no-dev --optimize-autoloader

# Garante que o .htaccess do Laravel esteja presente
RUN cp /app/public/.htaccess.example /app/public/.htaccess || true

# Cria .htaccess para corrigir o MIME do CSS/JS
RUN mkdir -p /app/public/build/assets \
    && echo '<FilesMatch "\.css$">' > /app/public/build/assets/.htaccess \
    && echo '    ForceType text/css' >> /app/public/build/assets/.htaccess \
    && echo '</FilesMatch>' >> /app/public/build/assets/.htaccess \
    && echo '<FilesMatch "\.js$">' >> /app/public/build/assets/.htaccess \
    && echo '    ForceType application/javascript' >> /app/public/build/assets/.htaccess \
    && echo '</FilesMatch>' >> /app/public/build/assets/.htaccess

# Ajusta permissões
RUN chown -R www-data:www-data /app \
    && chmod -R 775 /app/storage /app/bootstrap/cache \
    && chmod -R 755 /app/public

EXPOSE 80

RUN php artisan storage:link || true

