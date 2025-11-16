# -------- stage 1: build Vite --------
    FROM node:20-alpine AS assets
    WORKDIR /app
    
    COPY package*.json ./
    RUN npm ci
    
    # copie apenas o necessário p/ o build
    COPY vite.config.* ./
    COPY resources ./resources
    COPY public ./public
    # COPY tailwind.config.* postcss.config.* ./  # se usar
    
    # gera public/build/manifest.json
    RUN npm run build
    
    
    # -------- stage 2: vendors PHP (cacheável) --------
    FROM composer:2 AS vendor
    WORKDIR /app
    
    COPY composer.json composer.lock ./
    # copie só o que composer precisa analisar
    COPY app ./app
    COPY bootstrap ./bootstrap
    COPY config ./config
    COPY database ./database
    COPY routes ./routes
    COPY artisan ./
    
    RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
    
    
    # -------- stage 3: runtime Apache+PHP --------
    FROM webdevops/php-apache:8.2-alpine
    WORKDIR /app
    
    # Apache aponta p/ o public
    ENV WEB_DOCUMENT_ROOT=/app/public
    
    # copie o projeto (sem vendor/build)
    COPY . /app
    
    # traga vendor e os assets buildados
    COPY --from=vendor /app/vendor /app/vendor
    COPY --from=assets /app/public/build /app/public/build
    
    # permissões mínimas p/ Laravel
    RUN chown -R application:application /app \
     && find storage -type d -exec chmod 775 {} \; \
     && find storage -type f -exec chmod 664 {} \; \
     && chmod -R 775 bootstrap/cache
    
    # (opcional) se usar queues: já vem com supervisord na imagem
    # EXPOSE 80  # não precisa, mas pode deixar
    
    COPY entrypoint.sh /entrypoint.sh
    RUN chmod +x /entrypoint.sh
    
    ENTRYPOINT ["/entrypoint.sh"]
    

