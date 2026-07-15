<?php

use App\Logging\Handlers\ColoredLineFormatter;
use App\Logging\Handlers\DeduplicateHandlers;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    'channels' => [
        'stack_docker' => [
            'driver'            => 'stack',
            'channels'          => ['stderr', 'daily'],
            'ignore_exceptions' => false,
        ],

        'stack_docker_local' => [
            'driver'            => 'stack',
            'channels'          => ['stderr', 'daily'],
            'ignore_exceptions' => false,
        ],

        'daily' => [
            'driver'               => 'daily',
            'path'                 => storage_path('logs/laravel.log'),
            'level'                => env('LOG_LEVEL', 'debug'),
            'days'                 => 14,
            'replace_placeholders' => true,
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

        // When no webhook is configured (e.g. local/testing) discord must still resolve to a
        // valid channel, otherwise any stack that includes it (like 'scheduler') fails to build
        // and falls back to the emergency logger. A NullHandler makes discord logging a safe no-op.
        'discord' => empty(env('APP_LOG_DISCORD_WEBHOOK')) ? [
            'driver'  => 'monolog',
            'handler' => NullHandler::class,
        ] : [
            'driver' => 'custom',
            'url'    => env('APP_LOG_DISCORD_WEBHOOK'),
            'via'    => MarvinLabs\DiscordLogger\Logger::class,
            'level'  => 'error',
            // The same error repeated within the dedup window is sent to Discord only once
            'tap' => [DeduplicateHandlers::class],
            //            'formatter' => Monolog\Formatter\LineFormatter::class,
            //            'formatter_with' => [
            //                'format' => "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            //            ],
        ],
    ],

];
