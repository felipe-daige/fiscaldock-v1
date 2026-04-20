# ===== Stage 1: Dependências Composer =====
# Build otimizado com multi-stage para produção
FROM php:8.3-cli AS vendor

# Instalar dependências necessárias para Composer
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensão zip do PHP (necessária para Composer)
RUN docker-php-ext-install zip

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

# ===== Stage 2: Build Assets (Vite) =====
FROM node:20-alpine AS assets

WORKDIR /app

# Copiar arquivos necessários para npm
COPY package.json package-lock.json ./
RUN npm ci

# Copiar arquivos do projeto necessários para build
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./

# Buildar assets de produção
RUN npm run build

# Validar que o build foi bem-sucedido
RUN ls -lah /app/public/build/ || (echo "ERRO: Diretório public/build não foi criado" && exit 1)
RUN test -f /app/public/build/manifest.json || (echo "ERRO: manifest.json não foi criado pelo build do Vite. Conteúdo de /app/public/build:" && ls -lah /app/public/build/ && exit 1)
RUN echo "✓ Build do Vite concluído com sucesso. manifest.json encontrado."
# ===== Stage 3: PHP-FPM + Nginx + Supervisor =====
FROM php:8.3-fpm

WORKDIR /var/www/html

# Instalar dependências do sistema e Nginx + Supervisor
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    git \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_pgsql \
    zip \
    intl \
    opcache \
    bcmath \
    gd

# Instalar extensão Redis via PECL
RUN pecl install redis && docker-php-ext-enable redis

# Copiar vendor da etapa anterior
COPY --from=vendor /app/vendor ./vendor

# Copiar o restante do código da aplicação
COPY . .

# Copiar assets buildados do Vite DEPOIS do COPY . . para garantir que sobrescreva
# Criar o diretório caso não exista
RUN mkdir -p /var/www/html/public/build
COPY --from=assets /app/public/build ./public/build

# Validar que o manifest.json foi copiado corretamente
RUN test -f /var/www/html/public/build/manifest.json || (echo "ERRO CRÍTICO: manifest.json não encontrado após cópia. Conteúdo de public/build:" && ls -lah /var/www/html/public/build/ 2>&1 || echo "Diretório não existe" && exit 1)
RUN echo "✓ Assets do Vite copiados com sucesso. manifest.json verificado."

# Configurações PHP para produção
RUN { \
      echo "display_errors=Off"; \
      echo "display_startup_errors=Off"; \
      echo "log_errors=On"; \
      echo "error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT"; \
      echo "memory_limit=256M"; \
      echo "post_max_size=100M"; \
      echo "upload_max_filesize=100M"; \
      echo "max_execution_time=3600"; \
      echo "date.timezone=America/Sao_Paulo"; \
    } > /usr/local/etc/php/conf.d/99-laravel.ini \
    && { \
      echo "opcache.enable=1"; \
      echo "opcache.enable_cli=1"; \
      echo "opcache.memory_consumption=128"; \
      echo "opcache.interned_strings_buffer=16"; \
      echo "opcache.max_accelerated_files=10000"; \
      echo "opcache.validate_timestamps=0"; \
      echo "opcache.save_comments=1"; \
      echo "opcache.fast_shutdown=1"; \
    } > /usr/local/etc/php/conf.d/99-opcache.ini

# Copiar configurações
COPY deployment/nginx.conf /etc/nginx/sites-available/default
COPY deployment/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY deployment/php-fpm-pool.conf /usr/local/etc/php-fpm.d/zz-custom-pool.conf
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Limpar caches de dev e regenerar package discovery para --no-dev
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php bootstrap/cache/routes-v7.php \
    && php artisan package:discover --ansi

# Ajustar permissões
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && chown -R www-data:www-data /var/www/html \
    && mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Remover configuração padrão do Nginx
RUN rm -f /etc/nginx/sites-enabled/default \
    && ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Criar diretórios necessários para Supervisor
RUN mkdir -p /var/log/supervisor

EXPOSE 80

CMD ["/usr/local/bin/docker-entrypoint.sh"]
