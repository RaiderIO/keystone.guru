#!/usr/bin/env bash
tput setaf 2;
echo "Rolling back database..."
tput sgr0;
php artisan migrate:rollback "$@"
