<?php

return [

    'stores' => [
        'tmp_file' => [
            'driver'    => 'file',
            'path'      => env('CACHE_FILE_PATH', storage_path('framework/cache/data')),
            'lock_path' => env('CACHE_FILE_PATH', storage_path('framework/cache/data')),
        ],

        'redis' => [
            'driver'          => 'redis',
            'connection'      => 'default',
            'lock_connection' => 'default',
        ],

        'redis_model_cache' => [
            'driver'     => 'redis',
            'connection' => 'model_cache',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, or DynamoDB cache
    | stores there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */
    'prefix' => env('CACHE_PREFIX', ''),
];
