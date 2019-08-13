#!/usr/bin/env bash

# https://laravel.com/docs/5.6/configuration#maintenance-mode
php artisan down --message="Upgrading keystone.guru, we will be back stronger than ever shortly!" --retry=60

# get rid of all local mods first
git checkout .
git clean -f

# now update
git pull

./update_dependencies.sh production

# Restore echo server clients
./update_echo_clients.sh

./migrate.sh

# Drop and re-populate all dungeon data, it's designed to do this no worries
tput setaf 2;
echo "Refreshing seeded data..."
tput sgr0;
php artisan db:seed --database=migrate --force

# Clear any caches, we just updated
php artisan optimize:clear
# Do NOT generate config cache; this causes issues.
# php artisan config:cache
# Generate route cache
php artisan route:cache
# Restart queue processors
php artisan queue:restart

./sh/start_supervisor.sh

# All done!
php artisan up