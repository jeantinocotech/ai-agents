# Etapa 1: construir frontend com Vite
FROM node:18-alpine as frontend

WORKDIR /app

# Copia apenas os arquivos de dependência primeiro (melhor cache)
COPY package*.json ./
RUN npm ci --only=production

# Copia o resto dos arquivos
COPY . .

# Executa o build do Vite
RUN npm run build

# Etapa 2: preparar PHP + Apache com Laravel
FROM webdevops/php-apache:8.2-alpine

WORKDIR /app

ENV WEB_DOCUMENT_ROOT /app/public

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia os arquivos do projeto
COPY . /app

# Copia os assets buildados do Vite da etapa anterior
COPY --from=frontend /app/public/build /app/public/build

# Verifica se os assets foram copiados corretamente
RUN ls -la /app/public/build/ && ls -la /app/public/build/assets/ || echo "Assets não encontrados"

# Instala dependências PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Cria diretórios necessários
RUN mkdir -p /app/storage/logs \
    && mkdir -p /app/storage/framework/cache \
    && mkdir -p /app/storage/framework/sessions \
    && mkdir -p /app/storage/framework/views \
    && mkdir -p /app/bootstrap/cache

# Configurar MIME types para Apache - SOLUÇÃO PARA O PROBLEMA CSS
RUN echo "# MIME types for Vite assets" >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo "AddType text/css .css" >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo "AddType application/javascript .js" >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo "AddType application/json .json" >> /opt/docker/etc/httpd/conf.d/10-php.conf

# Criar configuração específica para assets do build
RUN echo '<LocationMatch "^/build/.*\.css$">' >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo '    ForceType text/css' >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo '</LocationMatch>' >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo '<LocationMatch "^/build/.*\.js$">' >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo '    ForceType application/javascript' >> /opt/docker/etc/httpd/conf.d/10-php.conf \
    && echo '</LocationMatch>' >> /opt/docker/etc/httpd/conf.d/10-php.conf

# Ajusta permissões
RUN chown -R application:application /app \
    && chmod -R 775 /app/storage /app/bootstrap/cache \
    && chmod -R 755 /app/public

# Gera key do Laravel se necessário
RUN php artisan config:cache || true

EXPOSE 80