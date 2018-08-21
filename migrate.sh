#!/usr/bin/env bash
tput setaf 2;
echo "Migrating database..."
tput sgr0;
php artisan migrate --database=migrate