#!/bin/sh

echo "Setting up environment..."
/usr/local/bin/setup-env.sh start

echo "Starting Laravel Echo Server..."
exec node /usr/local/src/bootstrap.js
