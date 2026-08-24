#!/bin/bash

set -e

cd /home/u492713652/domains/sattalives.com/public_html/liveapi

echo "Starting deployment..."

git fetch origin
git reset --hard origin/main

composer install --no-dev --optimize-autoloader

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan optimize

chmod -R 775 storage bootstrap/cache

echo "Deployment completed successfully."
