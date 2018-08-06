#!/bin/sh
php artisan clear-compiled
php artisan ide-helper:generate
php artisan ide-helper:meta