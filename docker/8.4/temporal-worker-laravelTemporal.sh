#!/usr/bin/env bash

set -e

echo "Starting Temporal Worker..."

# Check if process is running locally
if pgrep -f "temporal:work" > /dev/null; then
    echo "Temporal worker process already running!"
    return 1
fi

# Ensure Laravel is set up
if [ ! -f "/var/www/html/vendor/autoload.php" ]; then
    echo "Installing dependencies..."
    composer install --no-dev --optimize-autoloader
fi

if [ ! -f "/var/www/html/.env" ]; then
    echo "Creating .env file..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Start the Temporal worker
exec php artisan temporal:work laravelTemporal --workers=2
