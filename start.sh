#!/bin/bash

/usr/local/openresty/nginx/sbin/nginx -c /data/nginx.conf
composer config repo.packagist composer https://mirrors.aliyun.com/composer/
composer install
composer dumpautoload
if [[ ! -f ".env" ]]; then
    cp .env.example  .env &&
    chmod -R 777 .env
fi

# php artisan init:database
# php artisan init:env
# php artisan migrate
# php artisan db:seed --class=DatabaseSeeder
# php artisan init:admin
php bin/laravels restart


