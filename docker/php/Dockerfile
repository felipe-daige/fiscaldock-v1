# ===== Stage 1: dependências Composer =====
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

# Copia o código para rodar eventuais scripts que dependam dele
COPY . .

# ===== Stage 2: PHP-FPM de produção =====
FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

# Instala dependências de sistema e extensões PHP necessárias
RUN apk add --no-cache \
    bash \
    git \
    unzip \
    icu-dev \
    libzip-dev \
    postgresql-dev \
    oniguruma-dev \
    linux-headers \
    autoconf \
    make \
    g++ \
  && docker-php-ext-configure intl \
  && docker-php-ext-install \
    intl \
    pdo_pgsql \
    zip \
    opcache \
  && apk del autoconf make g++

# Copia vendor da etapa anterior
COPY --from=vendor /app/vendor ./vendor

# Copia o restante do código da aplicação
COPY . .

# Copiar configuração PHP-FPM customizada para timeout de 1 hora
COPY deployment/php-fpm-pool.conf /usr/local/etc/php-fpm.d/zz-custom-pool.conf

# Configurações de PHP e opcache (produção básica)
RUN { \
      echo "display_errors=Off"; \
      echo "display_startup_errors=Off"; \
      echo "log_errors=On"; \
      echo "error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT"; \
      echo "memory_limit=256M"; \
      echo "post_max_size=100M"; \
      echo "upload_max_filesize=100M"; \
      echo "max_execution_time=3600"; \
    } > /usr/local/etc/php/conf.d/99-laravel.ini \
  && { \
      echo "opcache.enable=1"; \
      echo "opcache.enable_cli=1"; \
      echo "opcache.memory_consumption=128"; \
      echo "opcache.interned_strings_buffer=16"; \
      echo "opcache.max_accelerated_files=10000"; \
      echo "opcache.validate_timestamps=0"; \
      echo "opcache.save_comments=1"; \
    } > /usr/local/etc/php/conf.d/99-opcache.ini

# Ajusta permissões para usuário do PHP-FPM
RUN addgroup -g 1000 app && adduser -G app -g app -s /bin/sh -D app \
  && chown -R app:app /var/www/html

# storage e bootstrap/cache com escrita
RUN mkdir -p storage bootstrap/cache \
  && chown -R app:app storage bootstrap/cache \
  && chmod -R ug+rwx storage bootstrap/cache

USER app

# Garante que o link de storage exista (não falha se já existir)
RUN php artisan storage:link || true

EXPOSE 9000

CMD ["php-fpm"]
