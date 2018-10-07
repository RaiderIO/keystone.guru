#!/usr/bin/env bash
# Upgrading to version 1.0 shell commands

tput setaf 2;
echo "Refreshing Affixes..."
tput sgr0;
php artisan db:seed --class=AffixSeeder --database=migrate