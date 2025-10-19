#!/bin/sh
set -e

# Copy .env.docker to .env if .env doesn't exist
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.docker .env
fi

# Generate APP_KEY if not set
if ! grep -q "APP_KEY=base64:" .env; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Laravel development server
# Render provides PORT environment variable
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
