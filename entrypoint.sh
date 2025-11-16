#!/usr/bin/env bash
set -e

cd /app

# Garantir permissões básicas (opcional, mas ajuda)
chown -R application:application /app || true
chmod -R 775 storage bootstrap/cache || true

# Link do storage (ignora se já existir)
php artisan storage:link || true

# Limpa e recompila caches
php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear

# Migrações (não derruba o container se não houver nada a migrar)
php artisan migrate --force || true

# Recria caches para prod
php artisan config:cache && php artisan route:cache || true

# Deixa o Apache/supervisord da imagem assumir (NÃO coloque mais nada depois deste exec)
exec /usr/bin/supervisord -n

