#!/bin/bash

# Corrige o symlink do Laravel (caso seja destruído ou criado incorretamente)
echo "⏳ Corrigindo link simbólico do Laravel..."
rm -f public/storage
ln -s ../storage/app/public public/storage
echo "✅ Symlink recriado com sucesso."

# Executa o comando original do container (por exemplo: PHP-FPM ou serve script)
exec "$@"
