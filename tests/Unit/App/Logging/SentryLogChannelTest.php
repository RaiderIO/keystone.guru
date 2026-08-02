<?php

namespace Tests\Unit\App\Logging;

use App\Logging\Handlers\DeduplicateHandlers;
use App\Logging\Handlers\FlushingDeduplicationHandler;
use App\Logging\Handlers\SkipsExceptionMirrors;
use App\Logging\Handlers\SkipsExceptionMirrorsHandler;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\DeduplicationHandler;
use Monolog\Handler\HandlerWrapper;
use Monolog\Logger as MonologLogger;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionProperty;
use Tests\TestCases\PublicTestCase;

/**
 * The 'sentry' channel is what turns error-level StructuredLogging records into Sentry issues - Integration::handles()
 * only ever sees uncaught exceptions. These tests guard the two ways that quietly stops working: the channel failing
 * to resolve without a DSN (the #3445 failure mode, which falls back to the emergency logger), and a stack being
 * added or edited without including it.
 */
#[Group('Logging')]
final class SentryLogChannelTest extends PublicTestCase
{
    #[Test]
    public function sentryChannel_givenEmptyDsn_resolvesToValidChannel(): void
    {
        // Arrange - a configured DSN selects the real driver, which is not the case this test guards
        $sentryConfig = config('logging.channels.sentry');
        if (!empty(config('sentry.dsn'))) {
            self::markTestSkipped('A Sentry DSN is configured; this test covers the empty-DSN default.');
        }

        // Act & Assert
        self::assertArrayHasKey('driver', $sentryConfig, 'sentry must be a valid channel even without a DSN.');
    }

    #[Test]
    public function sentryChannel_givenEmptyDsn_logsWithoutEmittingPhpErrors(): void
    {
        // Arrange - force a fresh resolution so channel resolution (and any warning) actually happens
        Log::forgetChannel('sentry');

        $capturedErrors = [];
        set_error_handler(static function (int $severity, string $message) use (&$capturedErrors): bool {
            $capturedErrors[] = $message;

            return true;
        });

        try {
            // Act
            Log::channel('sentry')->error('Regression check - the sentry channel must be a safe no-op without a DSN.');
        } finally {
            restore_error_handler();
        }

        // Assert
        self::assertSame(
            [],
            array_values(array_unique($capturedErrors)),
            'Logging to the sentry channel must not emit PHP warnings when the DSN is empty.',
        );
    }

    /**
     * The DSN-present shape of the channel is never exercised locally or in CI, so its taps are asserted here by
     * resolving that shape explicitly. Both matter and both fail silently: without the dedup tap's ':sentry' argument
     * this channel would share Discord's deduplication store and the two would suppress each other's records, and
     * without the mirror tap every uncaught exception would collapse into a single Sentry issue.
     */
    #[Test]
    public function sentryChannel_givenConfiguredDsn_wrapsHandlerInBothTaps(): void
    {
        // Arrange
        $loggingConfig   = require config_path('logging.php');
        $configuredShape = [
            'driver'            => 'sentry',
            'level'             => 'error',
            'report_exceptions' => false,
            'tap'               => $loggingConfig['channels']['sentry']['tap']
                ?? [DeduplicateHandlers::class . ':sentry', SkipsExceptionMirrors::class],
        ];

        config(['logging.channels.sentry_dsn_shape' => $configuredShape]);
        Log::forgetChannel('sentry_dsn_shape');

        // Act
        $channel = Log::channel('sentry_dsn_shape');

        // Assert
        self::assertInstanceOf(Logger::class, $channel);

        $monolog = $channel->getLogger();
        self::assertInstanceOf(MonologLogger::class, $monolog);

        $handlers = $monolog->getHandlers();
        self::assertCount(1, $handlers);

        $mirrorFilter = $handlers[0];
        self::assertInstanceOf(SkipsExceptionMirrorsHandler::class, $mirrorFilter);

        $deduplicator = (new ReflectionProperty(HandlerWrapper::class, 'handler'))->getValue($mirrorFilter);
        self::assertInstanceOf(FlushingDeduplicationHandler::class, $deduplicator);

        self::assertSame(
            storage_path('logs/sentry-deduplication.log'),
            (new ReflectionProperty(DeduplicationHandler::class, 'deduplicationStore'))->getValue($deduplicator),
            'The sentry channel must deduplicate against its own store, not discord\'s.',
        );
    }

    /**
     * Asserted against the configuration rather than a resolved logger on purpose: phpunit.xml pins
     * LOG_CHANNEL=daily, so resolving the default channel would not exercise any of the stacks.
     *
     * Scoped to the stacks this repository declares, by reading config/logging.php directly rather than the merged
     * configuration. Laravel merges the framework's own logging config in, which contributes a 'stack' channel built
     * from the LOG_STACK environment variable - that one is only reachable from the deployment environment, so
     * including it here would assert something this repository cannot satisfy.
     */
    #[Test]
    public function stackChannels_givenAnyStackDeclaredByThisApplication_includeSentry(): void
    {
        // Arrange
        $loggingConfig = require config_path('logging.php');
        /** @var array<string, array<string, mixed>> $channels */
        $channels = $loggingConfig['channels'];

        // Act
        $stacksWithoutSentry = [];
        foreach ($channels as $name => $channel) {
            if (($channel['driver'] ?? null) !== 'stack') {
                continue;
            }

            if (!in_array('sentry', $channel['channels'] ?? [], true)) {
                $stacksWithoutSentry[] = $name;
            }
        }

        // Assert
        self::assertSame(
            [],
            $stacksWithoutSentry,
            'Every log stack must include the sentry channel, or its errors never become Sentry issues.',
        );
    }
}
