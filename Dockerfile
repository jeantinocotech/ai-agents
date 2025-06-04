# Etapa 1: construir frontend com Vite
FROM node:18-alpine as frontend

WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Etapa 2: preparar PHP + Apache com Laravel
FROM webdevops/php-apache:8.2

WORKDIR /app

ENV WEB_DOCUMENT_ROOT /app/public

# Copia os arquivos da build do frontend e Laravel
COPY --from=frontend /app /app

# Garante que o .htaccess seja criado dentro da pasta public/build/assets
RUN echo '<FilesMatch "\.css$">' > /app/public/build/assets/.htaccess \
    && echo '    ForceType text/css' >> /app/public/build/assets/.htaccess \
    && echo '</FilesMatch>' >> /app/public/build/assets/.htaccess \
    && echo '<FilesMatch "\.js$">' >> /app/public/build/assets/.htaccess \
    && echo '    ForceType application/javascript' >> /app/public/build/assets/.htaccess \
    && echo '</FilesMatch>' >> /app/public/build/assets/.htaccess

# Instala dependências PHP
RUN composer install --no-dev --optimize-autoloader 

# Configurar MIME types para Apache - SOLUÇÃO PARA O PROBLEMA CSS
RUN echo "# MIME types for Vite assets" >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo "AddType text/css .css" >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo "AddType application/javascript .js" >> /opt/docker/etc/httpd/conf.d/10-php.conf

# Criar configuração específica para assets do build
RUN echo '<LocationMatch "^/build/.*\.css$">' >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo '    ForceType text/css' >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo '</LocationMatch>' >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo '<LocationMatch "^/build/.*\.js$">' >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo '    ForceType application/javascript' >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo '</LocationMatch>' >> /opt/docker/etc/httpd/conf.d/10-php.conf

# Ajusta permissões
RUN chown -R application:application /app \
    && chmod -R 755 /app/storage /app/bootstrap/cache \
    && chmod -R 755 /app/public

# Cria diretórios necessários se não existirem
RUN mkdir -p /app/storage/logs \
    && mkdir -p /app/storage/framework/cache \
    && mkdir -p /app/storage/framework/sessions \
    && mkdir -p /app/storage/framework/views \
    && mkdir -p /app/bootstrap/cache

# Cria o arquivo de log e ajusta todas as permissões
RUN touch /app/storage/logs/laravel.log \
    && chown -R application:application /app \
    && chmod -R 775 /app/storage \
    && chmod -R 775 /app/bootstrap/cache \
    && chmod -R 755 /app/public \
    && chmod 664 /app/storage/logs/laravel.log

EXPOSE 80