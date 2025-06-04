#!/bin/bash

echo "⏳ Corrigindo link simbólico do Laravel..."
rm -f /app/public/storage
ln -s /app/storage/app/public /app/public/storage
echo "✅ Symlink recriado com sucesso."

# Executa o Apache (comando padrão da imagem php:8.2-apache)
exec apache2-foreground

