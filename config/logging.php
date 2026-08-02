<?php

use App\Logging\Handlers\ColoredLineFormatter;
use App\Logging\Handlers\DeduplicateHandlers;
use App\Logging\Handlers\SkipsExceptionMirrors;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    'channels' => [
        'stack_docker' => [
            'driver'            => 'stack',
            'channels'          => ['stderr', 'daily', 'sentry'],
            'ignore_exceptions' => false,
        ],

        'stack_docker_local' => [
            'driver'            => 'stack',
            'channels'          => ['stderr', 'daily', 'sentry'],
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
            'channels' => ['scheduler_file', 'discord', 'sentry'],
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

        // Errors logged through StructuredLogging are not exceptions, so Integration::handles() in bootstrap/app.php
        // never sees them - this channel is what turns them into Sentry issues so they can be triaged from there
        // instead of only being read off Discord. Same empty-value guard as discord above: without a DSN the channel
        // must still resolve, or every stack including it falls back to the emergency logger (#3445). The sentry
        // driver is normally registered unconditionally by the SDK, but ServiceProvider::registerFeatures() swallows
        // any Throwable thrown while registering it, so the guard is not purely theoretical.
        'sentry' => empty(env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN'))) ? [
            'driver'  => 'monolog',
            'handler' => NullHandler::class,
        ] : [
            'driver' => 'sentry',
            'level'  => 'error',
            // Records carrying a Throwable under the 'exception' context key are already reported by the SDK with a
            // full stack trace; this only suppresses framework-level duplicates such as the emergency logger, since
            // StructuredLogging names its parameters $e/$ex/$throwable rather than $exception
            'report_exceptions' => false,
            // Order matters - the last tap wraps outermost, so mirrors are dropped before they reach the dedup store.
            // The ':sentry' argument gives this channel its own store; sharing discord's would make the two channels
            // suppress each other's records.
            'tap' => [DeduplicateHandlers::class . ':sentry', SkipsExceptionMirrors::class],
        ],
    ],

];
