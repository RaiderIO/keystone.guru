#!/usr/bin/env bash

#make sure we have the correct versions for everything
tput setaf 2;
echo "Updating dependencies..."
tput sgr0;
./update_dependencies.sh

# create directories
tput setaf 2;
echo "Creating directories..."
tput sgr0;
# cache for image library
mkdir storage/app/public/imagecache
mkdir storage/app/public/expansions

# ensure www-data permissions
# TODO: log folders writable?
tput setaf 2;
echo "Setting www-data ownership to some folders..."
tput sgr0;
chown -R www-data:www-data storage/*
chown -R www-data:www-data bootstrap/cache

# ensure some other permissions
tput setaf 2;
echo "Ensuring file permissions..."
tput sgr0;
chmod 755 ide_helper_regen.sh
chmod 755 update_dependencies.sh
chmod 755 refresh_autoload.sh
chmod 755 refresh_db_seed.sh
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# ensure any uploaded file may be accessed directly (symlinks public/storage to storage/app/public)
tput setaf 2;
echo "Ensuring storage link..."
tput sgr0;
php artisan storage:link

# This was somehow needed to get the image library to work
tput setaf 2;
echo "Publishing Folklore ImageServiceProvider..."
tput sgr0;
php artisan vendor:publish --provider="Folklore\Image\ImageServiceProvider"

tput setaf 2;
echo "Compiling sources..."
tput sgr0;
npm run dev -- --env.full true