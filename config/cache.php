<?php

return [

    'stores' => [
        'tmp_file' => [
            'driver'    => 'file',
            'path'      => env('CACHE_FILE_PATH', storage_path('framework/cache/data')),
            'lock_path' => env('CACHE_FILE_PATH', storage_path('framework/cache/data')),
        ],

        'redis_model_cache' => [
            'driver'     => 'redis',
            'connection' => 'model_cache',
        ],
    ],

];
