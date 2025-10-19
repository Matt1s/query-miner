#!/bin/sh
set -e

# Generate APP_KEY if it doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.docker .env
fi

# Check if APP_KEY is set in .env
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Run migrations (optional, but good for initialization)
# php artisan migrate --force

# Start PHP-FPM
exec php-fpm
