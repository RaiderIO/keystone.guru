<?php

// Using deployer - this will be the release number
// base_path() will be /var/www/html/<project name>/releases/350
// realpath will ensure symlinks are resolved
// basename will get the last part of the path which is 350
$release = env('APP_TYPE', 'local') !== 'local' ? basename(realpath(base_path())) : 'local';

return [
    'cache-prefix' => sprintf('release_%s:', $release),

    'enabled' => env('MODEL_CACHE_ENABLED', true),

    'use-database-keying' => env('MODEL_CACHE_USE_DATABASE_KEYING', true),

    'store' => env('MODEL_CACHE_STORE'),
];
