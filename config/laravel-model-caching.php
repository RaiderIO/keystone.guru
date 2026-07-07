<?php

$release = file_get_contents(base_path('version')) ?: 'unknown';

// This may give a double release number, but it is not a problem for now
return [
    'cache-prefix' => sprintf('{laravel-model-caching}:%s:', $release),

    'enabled' => env('MODEL_CACHE_ENABLED', true),

    'use-database-keying' => env('MODEL_CACHE_USE_DATABASE_KEYING', true),

    'store' => env('MODEL_CACHE_STORE'),
];
