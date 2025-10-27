#!/bin/sh

# Para a execução se qualquer comando falhar
set -e

# 1. Otimizações do Laravel (cache de config e rotas)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 2. Executar Migrations (Importante!)
# O Render pode injetar o DATABASE_URL, ou podemos usar as vars padrão
# Adicione lógica aqui para esperar o banco de dados estar pronto, se necessário
php artisan migrate --force # '--force' é crucial para rodar em produção sem confirmação

# 3. Iniciar o Nginx em background
nginx -g 'daemon off;' &

# 4. Iniciar o PHP-FPM em foreground (processo principal do container)
# 'exec' substitui o processo do shell pelo php-fpm, garantindo sinais corretos
exec php-fpm
