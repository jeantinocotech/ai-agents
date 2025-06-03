# Etapa 1: construir frontend com Vite
FROM node:18-alpine as frontend

WORKDIR /app
COPY package*.json ./

RUN npm install

# Copia todos os arquivos necessários para o build
COPY . .

# Verifica se tem tailwind.config.js
RUN echo "=== Verificando Tailwind ===" \
    && ls -la tailwind.config.js || echo "tailwind.config.js não encontrado" \
    && echo "=== Verificando postcss.config.js ===" \
    && ls -la postcss.config.js || echo "postcss.config.js não encontrado"

# Mostra estrutura antes do build
RUN echo "=== Arquivos resources ===" \
    && ls -la resources/css/ \
    && ls -la resources/js/ \
    && echo "=== Conteúdo app.css ===" \
    && head -10 resources/css/app.css

# Executa o build
RUN npm run build

# Verifica se os assets foram gerados corretamente
RUN echo "=== Verificando build ===" \
    && ls -la public/build/ \
    && echo "=== Assets gerados ===" \
    && ls -la public/build/assets/ || echo "Pasta assets não encontrada" \
    && echo "=== Manifest.json ===" \
    && cat public/build/manifest.json

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


# Cria o arquivo de log e ajusta todas as permissões
RUN touch /app/storage/logs/laravel.log \
    && chown -R application:application /app \
    && chmod -R 775 /app/storage \
    && chmod -R 775 /app/bootstrap/cache \
    && chmod -R 755 /app/public \
    && chmod 664 /app/storage/logs/laravel.log

# Gera a key se não existir (importante!)
RUN php artisan key:generate --force

# Testa se o Laravel está funcionando
RUN php artisan --version

# Limpa caches se existirem
RUN php artisan config:clear \
    && php artisan cache:clear \
    && php artisan view:clear

EXPOSE 80

