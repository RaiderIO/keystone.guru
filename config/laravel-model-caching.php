<?php

// Using deployer - this will be the release number
// base_path() will be /var/www/html/<project name>/releases/350
// realpath will ensure symlinks are resolved
// basename will get the last part of the path which is 350
$release = file_get_contents(base_path('version')) ?: 'unknown';

// This may give a double release number, but it is not a problem for now
return [
    'cache-prefix' => sprintf('laravel-model-caching:%s:', $release),

    'enabled' => env('MODEL_CACHE_ENABLED', true),

    'use-database-keying' => env('MODEL_CACHE_USE_DATABASE_KEYING', true),

    'store' => env('MODEL_CACHE_STORE'),
];
