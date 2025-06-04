# Etapa 1: construir frontend com Vite
FROM node:18-alpine as frontend

WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Etapa 2: PHP com Apache
FROM php:8.2-apache

# Ativa extensões e ferramentas necessárias
RUN apt-get update \
    && apt-get install -y unzip curl libzip-dev zip \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Define raiz do site como /app/public
ENV APACHE_DOCUMENT_ROOT /app/public
RUN sed -ri -e 's!/var/www/html!/app/public!g' /etc/apache2/sites-available/000-default.conf

WORKDIR /app

# Copia tudo do frontend + Laravel
COPY --from=frontend /app /app

# Instala dependências PHP (Laravel)
RUN composer install --no-dev --optimize-autoloader

# Corrige MIME do CSS/JS com .htaccess persistente
RUN mkdir -p /app/public/build/assets \
    && echo '<FilesMatch "\.css$">' > /app/public/build/assets/.htaccess \
    && echo '    ForceType text/css' >> /app/public/build/assets/.htaccess \
    && echo '</FilesMatch>' >> /app/public/build/assets/.htaccess \
    && echo '<FilesMatch "\.js$">' >> /app/public/build/assets/.htaccess \
    && echo '    ForceType application/javascript' >> /app/public/build/assets/.htaccess \
    && echo '</FilesMatch>' >> /app/public/build/assets/.htaccess

# Ajusta permissões Laravel
RUN chown -R www-data:www-data /app \
    && chmod -R 775 /app/storage /app/bootstrap/cache

EXPOSE 80
