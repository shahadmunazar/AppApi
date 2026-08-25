#!/bin/bash

set -e

cd /home/u492713652/domains/sattalives.com/public_html/liveapi

echo "Starting deployment..."

git fetch origin
git reset --hard origin/ShahadAppApi

export COMPOSER_HOME=/tmp
composer install --no-dev --optimize-autoloader

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear


php artisan migrate --force

php artisan optimize

mkdir -p uploads
chmod -R 775 storage bootstrap/cache uploads

echo "Deployment completed successfully."
