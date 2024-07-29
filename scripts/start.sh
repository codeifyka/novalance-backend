#!/bin/bash

# Navigate to the frontend directory
cd frontend

echo "# Installing dependencies..."
npm install

echo "# Building..."
npm run build

cd ../

# Navigate to the websocket-server directory
cd websocket-server

echo "# Installing dependencies..."
npm install

cd ../

echo "# JWT Secret Generate..."
php artisan jwt:secret

echo "# Link storage..."
php artisan storage:link

echo "# Start the PHP application in the background"
php artisan serve --host=0.0.0.0 --port=8000 &

# Navigate to the websocket-server directory
cd websocket-server

# Start the websocket server in the background
npm run dev &

cd ../

# Navigate back to the frontend directory
cd frontend

echo "# Start the Node.js application concurrently"
npm run preview