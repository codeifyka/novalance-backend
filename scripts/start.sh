#!/bin/bash

# Ensure .env and APP_KEY exist at runtime if not provided via mount/env
if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ -z "$APP_KEY" ] && ! grep -q "^APP_KEY=.\+" .env 2>/dev/null; then
    php artisan key:generate --force
fi

# Navigate to the websocket-server directory
cd websocket-server

echo "# Installing dependencies..."
npm install

cd ../

if [ -z "$JWT_SECRET" ] && ! grep -q "^JWT_SECRET=.\+" .env 2>/dev/null; then
    echo "# JWT Secret Generate..."
    php artisan jwt:secret --force
fi

echo "# Run pending database migrations..."
php artisan migrate --force

# Seed only if the database has no users yet (fresh deployment)
if [ "$(php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo \App\Models\User::count();' 2>/dev/null)" = "0" ]; then
    echo "# Fresh database detected, running initial seeders..."
    php artisan db:seed --force
fi

echo "# Link storage..."
php artisan storage:link

echo "# Start the PHP application in the background"
php artisan serve --host=0.0.0.0 --port=8000 &

echo "# Start Laravel Reverb WebSocket server in the background"
php artisan reverb:start --host=0.0.0.0 --port=8080 &

# Navigate to the websocket-server directory
cd websocket-server

# Start the websocket server in the background
npm run dev