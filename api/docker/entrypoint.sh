#!/bin/bash
set -e

echo "Waiting for database server..."
until PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "postgres" -c '\l' >/dev/null 2>&1; do
    echo "Postgres is unavailable - sleeping"
    sleep 2
done

# Wait for specific database to be ready
until PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -c '\q' >/dev/null 2>&1; do
    echo "Database ${DB_DATABASE} is not ready - sleeping"
    sleep 2
done

echo "Database is ready!"

echo "Install/Update dependencies..."
composer install --no-interaction --prefer-dist --optimize-autoloader

echo "Run migrations..."
php artisan migrate --force

echo "Clear caches..."
php artisan config:clear
php artisan cache:clear

echo "Generate application..."
php artisan key:generate --force

echo "Cache configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Start PHP-FPM..."
exec php-fpm
