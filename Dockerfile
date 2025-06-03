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
# Copia arquivos de frontend gerados pelo Vite
COPY --from=frontend /app/public/build /app/public/build
COPY --from=frontend /app/public/manifest.json /app/public/manifest.json
#COPY --from=frontend /app /app

# Instala dependências PHP
RUN composer install --no-dev --optimize-autoloader \
    && chown -R application:application /app \
    && chmod -R 755 /app/public /app/storage /app/bootstrap/cache

# Ajusta permissões
RUN chown -R application:application /app \
    && chmod -R 755 /app/storage /app/bootstrap/cache

EXPOSE 80

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
#CMD ["supervisord", "-n"]

