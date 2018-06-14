#!/usr/bin/env bash

# create directories
echo "Creating directories..."
# cache for image library
mkdir storage/app/public/imagecache
mkdir storage/app/public/expansions

# ensure www-data permissions
# TODO: log folders writable?
echo "Setting www-data ownership to some folders..."
chown -R www-data:www-data storage/*

# ensure some other permissions
echo "Ensuring file permissions..."
chmod 755 ide_helper_regen.sh
chmod 755 update_dependencies.sh
chmod 755 refresh_autoload.sh
chmod 755 refresh_db_seed.sh
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# ensure any uploaded file may be accessed directly (symlinks public/storage to storage/app/public)
echo "Ensuring storage link..."
php artisan storage:link

#make sure we have the correct versions for everything
echo "Updating dependencies..."
./update_dependencies.sh

# This was somehow needed to get the image library to work
echo "Publishing Folklore ImageServiceProvider..."
php artisan vendor:publish --provider="Folklore\Image\ImageServiceProvider"