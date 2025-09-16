<?php /** @noinspection PhpComposerExtensionStubsInspection */

use Illuminate\Support\Str;

return [

    'connections' => [
        'mysql' => [
            'driver'         => 'mysql',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '3306'),
            'database'       => env('DB_DATABASE', 'forge'),
            'username'       => env('DB_USERNAME', 'forge'),
            'password'       => env('DB_PASSWORD', ''),
            'unix_socket'    => env('DB_SOCKET', ''),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => false, // this is probably a bad idea, https://stackoverflow.com/questions/43776758/how-can-i-solve-incompatible-with-sql-mode-only-full-group-by-in-laravel-eloquen
            'engine'         => null,
            'options'        => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'migrate' => [
            'driver'         => 'mysql',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '3306'),
            'database'       => env('DB_DATABASE', 'forge'),
            'username'       => env('DB_MIGRATION_USERNAME', 'forge'),
            'password'       => env('DB_MIGRATION_PASSWORD', ''),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => true,
            'engine'         => null,
            'options'        => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'phpunit' => [
            'driver'         => 'mysql',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '3306'),
            'database'       => env('DB_PHPUNIT_DATABASE', 'forge'),
            'username'       => env('DB_PHPUNIT_USERNAME', 'forge'),
            'password'       => env('DB_PHPUNIT_PASSWORD', ''),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => false, // this is probably a bad idea, https://stackoverflow.com/questions/43776758/how-can-i-solve-incompatible-with-sql-mode-only-full-group-by-in-laravel-eloquen
            'engine'         => null,
            'options'        => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'tracker' => [
            'driver'         => 'mysql',
            'url'            => env('DB_URL'),
            'host'           => env('DB_HOST', '127.0.0.1'),
            'port'           => env('DB_PORT', '3306'),
            'database'       => env('DB_TRACKER_DATABASE', 'forge'),
            'username'       => env('DB_TRACKER_USERNAME', 'forge'),
            'password'       => env('DB_TRACKER_PASSWORD', ''),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => false,
            'engine'         => null,
            'options'        => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'combatlog' => [
            'driver'         => 'mysql',
            'url'            => env('DB_URL'),
            'host'           => env('DB_COMBATLOG_HOST', '127.0.0.1'),
            'port'           => env('DB_COMBATLOG_PORT', '3306'),
            'database'       => env('DB_COMBATLOG_DATABASE', 'forge'),
            'username'       => env('DB_COMBATLOG_USERNAME', 'forge'),
            'password'       => env('DB_COMBATLOG_PASSWORD', ''),
            'charset'        => 'utf8mb4',
            'collation'      => 'utf8mb4_unicode_ci',
            'prefix'         => '',
            'prefix_indexes' => true,
            'strict'         => false,
            'engine'         => null,
            'options'        => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
    ],

    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => false, // disable to preserve original behavior for existing applications
    ],

    'redis' => [

        'client' => env('REDIS_CLIENT', 'predis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix'  => env(
                'REDIS_PREFIX',
                sprintf(
                    '%s-%s-cache:',
                    Str::slug(env('APP_NAME', 'laravel')),
                    Str::slug(env('APP_TYPE', 'local')),
                ),
            ),
        ],

        'default' => [
            'url'          => env('REDIS_URL'),
            'host'         => env('REDIS_HOST', '127.0.0.1'),
            'username'     => env('REDIS_USERNAME'),
            'password'     => env('REDIS_PASSWORD'),
            'port'         => env('REDIS_PORT', '6379'),
            'database'     => env('REDIS_DB', '0'),
            'read_timeout' => 1.0,
            'timeout'      => 1.0,
            //            'persistent'   => false, // or true if you use connection pooling
        ],

        'model_cache' => [
            'url'          => env('REDIS_URL'),
            'host'         => env('REDIS_HOST', '127.0.0.1'),
            'username'     => env('REDIS_USERNAME'),
            'password'     => env('REDIS_PASSWORD'),
            'port'         => env('REDIS_PORT', '6379'),
            'database'     => env('REDIS_DB_MODEL_CACHE', '0'),
            'read_timeout' => 1.0,
            'timeout'      => 1.0,
            //            'persistent'   => false, // or true if you use connection pooling
        ],

        'cache' => [
            'url'          => env('REDIS_URL'),
            'host'         => env('REDIS_HOST', '127.0.0.1'),
            'username'     => env('REDIS_USERNAME'),
            'password'     => env('REDIS_PASSWORD'),
            'port'         => env('REDIS_PORT', '6379'),
            'database'     => env('REDIS_CACHE_DB', '1'),
            'read_timeout' => 1.0,
            'timeout'      => 1.0,
            //            'persistent'   => false, // or true if you use connection pooling
        ],

    ],

];
