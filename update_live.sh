#!/usr/bin/env bash

# https://laravel.com/docs/5.6/configuration#maintenance-mode
# Go up first in case we were manually down, otherwise the message will not get refreshed
php artisan up
php artisan down --render="errors::503" --retry=60

# get rid of all local mods first
git checkout .
git clean -f

# now update
git pull

./update_dependencies.sh production

# see https://github.com/laravel/horizon/blob/master/UPGRADE.md
php artisan horizon:publish

./migrate.sh

# Drop and re-populate all dungeon data, it's designed to do this no worries
tput setaf 2;
echo "Refreshing seeded data..."
tput sgr0;
php artisan db:seed --database=migrate --force

# Clear any caches, we just updated
php artisan optimize:clear
# Generate route cache
php artisan route:cache
# Generate config cache
php artisan config:clear
# Restart queue processors
php artisan queue:restart
# Start supervisor related tasks
php artisan supervisor:start

# All done!
php artisan up