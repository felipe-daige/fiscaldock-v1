#!/bin/bash
set -e

echo "=== Iniciando entrypoint da aplicação ==="

# Aguardar conexão com Postgres (opcional, usando PHP)
if [ -n "$DB_HOST" ]; then
    echo "Aguardando conexão com PostgreSQL em $DB_HOST:${DB_PORT:-5432}..."
    for i in {1..15}; do
        if php -r "try { new PDO('pgsql:host=${DB_HOST};port=${DB_PORT:-5432}', '${DB_USERNAME}', '${DB_PASSWORD}'); echo 'PostgreSQL disponível!\n'; exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; then
            break
        fi
        echo "Tentativa $i/15 - PostgreSQL não está disponível ainda, aguardando..."
        sleep 2
    done
fi

# Corrigir permissões do storage e bootstrap/cache
echo "Ajustando permissões..."
# Garantir que os diretórios existam
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/bootstrap/cache
# Ajustar permissões (usar find para evitar problemas com volumes montados)
find /var/www/html/storage -type d -exec chmod 775 {} \;
find /var/www/html/storage -type f -exec chmod 664 {} \;
find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;
find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \;
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Criar symlink de storage se não existir
if [ ! -L /var/www/html/public/storage ]; then
    echo "Criando symlink de storage..."
    php artisan storage:link || echo "Aviso: storage:link falhou ou já existe"
fi

# Executar migrações
echo "Executando migrações do banco de dados..."
php artisan migrate --force || echo "Aviso: Migrações falharam, continuando..."

# Limpar caches antigos (se existirem)
echo "Limpando caches antigos..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Verificar webhooks configurados
echo "=== Verificando webhooks ==="
if [ -f /var/www/html/.env ]; then
    echo "Arquivo .env encontrado"
    WEBHOOK_COUNT=$(grep -c "^WEBHOOK_" /var/www/html/.env 2>/dev/null || echo "0")
    echo "Webhooks no .env: ${WEBHOOK_COUNT}"
else
    echo "AVISO: Arquivo .env NAO encontrado - dependendo apenas de env vars do container"
fi
if [ -n "$WEBHOOK_IMPORTACAO_XML_URL" ]; then
    echo "Webhook XML: configurado"
else
    echo "AVISO: WEBHOOK_IMPORTACAO_XML_URL vazio"
fi
if [ -n "$WEBHOOK_CONSULTAS_CNPJ_URL" ]; then
    echo "Webhook Consultas CNPJ: configurado"
else
    echo "AVISO: WEBHOOK_CONSULTAS_CNPJ_URL vazio"
fi

# Gerar caches de produção
echo "Gerando caches de produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verificar se API_TOKEN foi cacheado corretamente sem expor prefixo do segredo
API_TOKEN_STATUS=$(php -r "\$c = require '/var/www/html/bootstrap/cache/config.php'; \$t = \$c['services']['api']['token'] ?? ''; echo \$t ? 'OK (len=' . strlen(\$t) . ')' : 'VAZIO';")
echo "API_TOKEN no config cache: ${API_TOKEN_STATUS}"

# IMPORTANTE: Corrigir permissões DEPOIS de gerar caches
# Os comandos acima rodam como root, mas PHP-FPM roda como www-data
echo "Corrigindo permissões dos caches gerados..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

echo "=== Caches gerados com sucesso ==="

# Configurar PHP-FPM timeout para 1 hora
echo "Configurando PHP-FPM timeout para 1 hora..."
if [ -f /usr/local/etc/php-fpm.d/www.conf ]; then
    # Remove linha antiga se existir (com ou sem comentário)
    sed -i '/^[; ]*request_terminate_timeout/d' /usr/local/etc/php-fpm.d/www.conf
    # Adiciona nova configuração após [www]
    sed -i '/^\[www\]/a request_terminate_timeout = 3600' /usr/local/etc/php-fpm.d/www.conf
    echo "✓ PHP-FPM timeout configurado para 3600 segundos (1 hora)"
elif [ -f /usr/local/etc/php-fpm.d/zz-custom-pool.conf ]; then
    # Se usar arquivo customizado, garantir que está correto
    sed -i 's/^[; ]*request_terminate_timeout.*/request_terminate_timeout = 3600/' /usr/local/etc/php-fpm.d/zz-custom-pool.conf
    grep -q "^request_terminate_timeout" /usr/local/etc/php-fpm.d/zz-custom-pool.conf || \
        sed -i '/^\[www\]/a request_terminate_timeout = 3600' /usr/local/etc/php-fpm.d/zz-custom-pool.conf
    echo "✓ PHP-FPM timeout configurado no arquivo customizado"
fi

echo "=== Iniciando Supervisor (Nginx + PHP-FPM) ==="

# Iniciar Supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
