#!/bin/sh

echo "Setting up environment..."
/usr/local/bin/setup-env.sh start

echo "Starting Laravel Echo Server..."
exec laravel-echo-server start --force --config /app/docker-compose/laravel-echo-server/laravel-echo-server.json
