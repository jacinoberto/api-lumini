# Use a imagem oficial do PHP 8.2 com FPM (FastCGI Process Manager)
FROM php:8.2-fpm

# Defina o diretório de trabalho dentro do container
WORKDIR /var/www/html

# Instale dependências do sistema, Nginx e extensões PHP necessárias para o Laravel
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libpq-dev \ # Para PostgreSQL (remover se usar MySQL/nenhum BD específico aqui)
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip pdo_pgsql # Adicione pdo_pgsql se usar PostgreSQL

# Limpe o cache do apt
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instale o Composer (Gerenciador de dependências PHP)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copie os arquivos de dependência primeiro para aproveitar o cache do Docker
COPY composer.json composer.lock ./
# Instala apenas dependências de produção e otimiza o autoloader
RUN composer install --no-dev --no-interaction --no-plugins --no-scripts --prefer-dist --optimize-autoloader

# Copie o restante do código da aplicação
COPY . .

# Copiar a configuração do Nginx para o local correto
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Ajuste as permissões para o diretório de storage e bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copiar e tornar o script de entrypoint executável
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Exponha a porta 80, que o Nginx usará
EXPOSE 80

# Defina o entrypoint e o comando padrão
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
