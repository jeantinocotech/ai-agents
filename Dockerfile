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

# Instala dependências PHP
RUN composer install --no-dev --optimize-autoloader

# Configurações do Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Ajusta permissões
RUN chown -R application:application /app \
    && chmod -R 775 /app/storage /app/bootstrap/cache \
    && chmod -R 755 /app/public

# Garante que os assets estejam acessíveis
RUN chown -R application:application /app/public \
    && chmod -R 755 /app/public

EXPOSE 80

#COPY entrypoint.sh /entrypoint.sh
#RUN chmod +x /entrypoint.sh
#ENTRYPOINT ["/entrypoint.sh"]

#CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
#CMD ["supervisord", "-n"]

