#!/bin/sh

# Create some dirs
mkdir /var/www/storage/app/public/imagecache &&
  mkdir /var/www/storage/app/public/expansions &&
  mkdir /var/www/storage/debugbar

# Ensure the site works at all
php artisan storage:link
php artisan vendor:publish --provider="Folklore\Image\ImageServiceProvider" &&
  php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider" &&
  php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider" &&
  php artisan vendor:publish --tag=language

php artisan horizon:install &&
  php artisan horizon:publish

# Ensure there's default users in the database
php artisan db:seed --class=LaratrustSeeder --database=migrate

# Ensure the mapping is up to date
php artisan environment:update local
