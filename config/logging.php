<?php

use App\Logging\Handlers\ColoredLineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    'channels' => [
        'stack_docker' => [
            'driver'            => 'stack',
            'channels'          => ['stderr', 'daily', 'discord', /*'rollbar'*/],
            'ignore_exceptions' => false,
        ],

        'stack_docker_local' => [
            'driver'            => 'stack',
            'channels'          => ['stderr', 'daily', 'discord', /*'rollbar'*/],
            'ignore_exceptions' => false,
        ],

        'scheduler' => [
            'driver'   => 'stack',
            'channels' => ['scheduler_file', 'discord'],
        ],

        'scheduler_file' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/scheduler.log'),
            'level'  => 'debug',
            'days'   => 14,
        ],

        'stdout' => [
            'driver'    => 'monolog',
            'level'     => env('LOG_LEVEL', 'debug'),
            'tap'       => [ColoredLineFormatter::class],
            'handler'   => StreamHandler::class,
            'formatter' => env('LOG_STDOUT_FORMATTER'),
            'with'      => [
                'stream' => 'php://stdout',
                'level'  => env('LOG_LEVEL', 'debug'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver'    => 'monolog',
            'level'     => env('LOG_LEVEL', 'debug'),
            'tap'       => [ColoredLineFormatter::class],
            'handler'   => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with'      => [
                'stream' => 'php://stderr',
                'level'  => env('LOG_LEVEL', 'debug'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
            'stream'     => 'php://stderr',
        ],

        'discord' => empty(env('APP_LOG_DISCORD_WEBHOOK')) ? [] : [
            'driver' => 'custom',
            'url'    => env('APP_LOG_DISCORD_WEBHOOK'),
            'via'    => MarvinLabs\DiscordLogger\Logger::class,
            'level'  => 'error',
            //            'formatter' => Monolog\Formatter\LineFormatter::class,
            //            'formatter_with' => [
            //                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            //            ],
        ],
    ],

];
