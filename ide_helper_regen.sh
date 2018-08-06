#!/bin/sh
tput setaf 2;
echo "Regenerating IDE Helper..."
tput sgr0;
php artisan clear-compiled
php artisan ide-helper:generate
php artisan ide-helper:meta