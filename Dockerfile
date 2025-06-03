# Etapa 1: construir frontend com Vite
FROM node:18-alpine as frontend

WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .

# Executa o build
RUN npm run build

# Etapa 2: preparar PHP + Apache com Laravel
FROM webdevops/php-apache:8.2-alpine

WORKDIR /app

ENV WEB_DOCUMENT_ROOT /app/public

# Copia os arquivos da build do frontend e Laravel
COPY --from=frontend /app /app

# Instala dependências PHP
RUN composer install --no-dev --optimize-autoloader 

# Ajusta permissões
RUN chown -R application:application /app \
    && chmod -R 755 /app/storage /app/bootstrap/cache

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
