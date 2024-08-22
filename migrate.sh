#!/usr/bin/env bash
tput setaf 2;
echo "Migrating database..."
tput sgr0;
php artisan migrate --database=migrate --force

tput setaf 2;
echo "Migrating combatlog database..."
tput sgr0;
php artisan migrate --database=combatlog --path="database/migrations_combatlog" --force
