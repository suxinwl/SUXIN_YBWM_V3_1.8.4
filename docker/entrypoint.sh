#!/bin/sh
set -eu

cd /data

mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

chmod -R 777 storage bootstrap/cache || true
rm -f storage/laravels.pid storage/laravels-timer-process.pid supervisord.pid || true

echo "Waiting for MySQL..."
attempt=0
until php -r '$host=getenv("DB_HOST") ?: "mysql"; $port=getenv("DB_PORT") ?: "3306"; $db=getenv("DB_DATABASE") ?: "ybybyb"; $user=getenv("DB_USERNAME") ?: "root"; $pass=getenv("DB_PASSWORD") ?: ""; try { new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $user, $pass); exit(0); } catch (Throwable $e) { fwrite(STDERR, $e->getMessage().PHP_EOL); exit(1); }'; do
    attempt=$((attempt + 1))
    if [ "$attempt" -gt 60 ]; then
        echo "MySQL did not become ready in time."
        exit 1
    fi
    sleep 2
done

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan cache:clear || true

/usr/local/openresty/nginx/sbin/nginx -c /data/nginx.conf

exec php bin/laravels start
