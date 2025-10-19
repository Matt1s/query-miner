#!/bin/sh
set -e

echo "Starting Query Miner..."

# Create .env file from environment variables
cat > .env << EOF
APP_NAME="${APP_NAME:-Query Miner}"
APP_ENV="${APP_ENV:-production}"
APP_DEBUG="${APP_DEBUG:-true}"
APP_URL=https://query-miner.onrender.com

LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_LEVEL=${LOG_LEVEL:-debug}

SESSION_DRIVER=${SESSION_DRIVER:-file}
CACHE_DRIVER=${CACHE_DRIVER:-file}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}

GOOGLE_API_KEY=${GOOGLE_API_KEY}
GOOGLE_CX=${GOOGLE_CX}

DB_CONNECTION=sqlite
EOF

# Generate APP_KEY
echo "Generating application key..."
php artisan key:generate --force

# Show environment for debugging
echo "Environment configured:"
echo "APP_ENV: ${APP_ENV}"
echo "APP_DEBUG: ${APP_DEBUG}"
echo "PORT: ${PORT}"

# Don't cache in production to allow env var changes
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache

echo "Starting server on port ${PORT}..."

# Start Laravel development server
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
