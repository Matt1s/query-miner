#!/bin/sh
set -e

# Install dependencies if vendor directory doesn't exist (due to volume mount)
if [ ! -d "vendor" ]; then
    echo "Installing Composer dependencies..."
    composer install --optimize-autoloader --no-dev
fi

# Install node dependencies and build assets if node_modules doesn't exist
if [ ! -d "node_modules" ]; then
    echo "Installing npm dependencies..."
    npm install
    echo "Building assets..."
    npm run build
fi

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
