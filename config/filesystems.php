<?php

return [

    'cloud' => 's3',

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app/private'),
            'throw'  => false,
            'serve'  => true,
            'report' => false,
        ],

        's3_user_uploads' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_S3_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_S3_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_S3_BUCKET_USER_UPLOADS_REGION'),
            'bucket'                  => env('AWS_S3_BUCKET_USER_UPLOADS'),
            'url'                     => env('AWS_S3_BUCKET_USER_UPLOADS_URL'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => true,
        ],

        's3_hotfixes' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_S3_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_S3_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_S3_BUCKET_HOTFIXES_REGION'),
            'bucket'                  => env('AWS_S3_BUCKET_HOTFIXES'),
            'url'                     => '',
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => true,
        ],

        's3_combat_logs' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_S3_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_S3_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_S3_BUCKET_COMBAT_LOGS_REGION'),
            'bucket'                  => env('AWS_S3_BUCKET_COMBAT_LOGS'),
            'url'                     => '',
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => true,
        ],

        // Raider.IO's daily list of the NPCs that kill players in the open world, read by openworldnpc:sync
        's3_open_world_npcs' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_S3_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_S3_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_S3_BUCKET_OPEN_WORLD_NPCS_REGION'),
            'bucket'                  => env('AWS_S3_BUCKET_OPEN_WORLD_NPCS'),
            'url'                     => '',
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => true,
        ],

        // The portraits the in-game pipeline writes into the assets repository, mounted next to this checkout
        'enemy_portraits' => [
            'driver' => 'local',
            'root'   => base_path('../keystone.guru.assets/images/enemyportraits'),
            'throw'  => false,
        ],

    ],

];
