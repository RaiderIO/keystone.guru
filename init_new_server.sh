#!/usr/bin/env bash

# create directories
tput setaf 2;
echo "Creating directories..."
tput sgr0;
# cache for image library
mkdir storage/app/public/imagecache
mkdir storage/app/public/expansions
mkdir storage/debugbar

# ensure www-data permissions
tput setaf 2;
echo "Setting www-data ownership to some folders..."
tput sgr0;
chown -R www-data:www-data storage/*
chown -R www-data:www-data bootstrap/cache

# ensure some other permissions
tput setaf 2;
echo "Ensuring file permissions..."
tput sgr0;
chmod 755 *.sh
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# make sure setfacl is installed on the server
sudo apt-get install acl
# Give www-data user permissing to write in this folder regardless of ownership. See https://stackoverflow.com/a/29882246/771270
setfacl -d -m g:www-data:rwx storage/logs

# ensure any uploaded file may be accessed directly (symlinks public/storage to storage/app/public)
tput setaf 2;
echo "Ensuring storage link..."
tput sgr0;
php artisan storage:link

#make sure we have the correct versions for everything
tput setaf 2;
echo "Updating dependencies..."
tput sgr0;
./update_dependencies.sh

# This was somehow needed to get the image library to work
tput setaf 2;
echo "Publishing service providers..."
tput sgr0;
php artisan vendor:publish --provider="Folklore\Image\ImageServiceProvider"
php artisan vendor:publish --provider="Barryvdh\Debugbar\ServiceProvider"
php artisan vendor:publish --provider="PragmaRX\\Tracker\\Vendor\\Laravel\\ServiceProvider"

# In case Tracker is not working, check this -> https://github.com/antonioribeiro/tracker#installing
tput setaf 2;
echo "Setting up tracker..."
tput sgr0;
php artisan tracker:tables

# Run migrate again to fix the tracker
./migrate.sh

# Install some packages
git clone https://github.com/BlackrockDigital/startbootstrap-sb-admin-2.git public/templates/sb-admin-2
cd public/templates/sb-admin-2
git checkout tags/v3.3.7+1
git checkout -b v3.3.7+1
# Back to where we came from
cd ../../..

sudo apt-get install supervisor

# Seeding database
tput setaf 2;
echo "Seeding database..."
tput sgr0;
./refresh_db_seed.sh