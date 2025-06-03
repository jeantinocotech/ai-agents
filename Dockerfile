# Etapa 1: construir frontend com Vite
FROM node:18-alpine as frontend

WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Etapa 2: preparar PHP + Apache com Laravel
FROM webdevops/php-apache:8.2-alpine

WORKDIR /app

ENV WEB_DOCUMENT_ROOT /app/public

# Copia os arquivos da build do frontend e Laravel
COPY --from=frontend /app /app

# Instala dependências PHP (sem cache ainda)
RUN composer install --no-dev --optimize-autoloader

# Cria diretórios necessários se não existirem
RUN mkdir -p /app/storage/logs \
    && mkdir -p /app/storage/framework/cache \
    && mkdir -p /app/storage/framework/sessions \
    && mkdir -p /app/storage/framework/views \
    && mkdir -p /app/bootstrap/cache

# Cria arquivo .env se não existir (baseado no .env.example)
RUN if [ ! -f /app/.env ]; then \
        if [ -f /app/.env.example ]; then \
            cp /app/.env.example /app/.env; \
        else \
            echo "APP_NAME=Laravel" > /app/.env && \
            echo "APP_ENV=production" >> /app/.env && \
            echo "APP_KEY=" >> /app/.env && \
            echo "APP_DEBUG=true" >> /app/.env && \
            echo "APP_URL=http://localhost" >> /app/.env && \
            echo "" >> /app/.env && \
            echo "LOG_CHANNEL=stack" >> /app/.env && \
            echo "LOG_LEVEL=debug" >> /app/.env; \
        fi \
    fi

# Ajusta permissões ANTES dos comandos artisan
RUN chown -R application:application /app \
    && chmod -R 775 /app/storage \
    && chmod -R 775 /app/bootstrap/cache \
    && chmod -R 755 /app/public

# Gera a key se não existir (importante!)
RUN php artisan key:generate --force

# Testa se o Laravel está funcionando
RUN php artisan --version

# Configura logs para aparecer no stdout/stderr
RUN ln -sf /dev/stdout /app/storage/logs/laravel.log

EXPOSE 80

