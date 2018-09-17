#!/usr/bin/env bash

# https://laravel.com/docs/5.6/configuration#maintenance-mode
php artisan down --message="Upgrading keystone.guru, we will be back stronger than ever shortly!" --retry=60

# get rid of all local mods first
git checkout .

# now update
git pull

./update_dependencies.sh production

./migrate.sh

# Drop and re-populate all dungeon data, it's designed to do this no worries
tput setaf 2;
echo "Refreshing Dungeons..."
tput sgr0;
php artisan db:seed --class=DungeonsSeeder --database=migrate

tput setaf 2;
echo "Refreshing DungeonData..."
tput sgr0;
php artisan db:seed --class=DungeonDataSeeder --database=migrate

# All done!
php artisan up