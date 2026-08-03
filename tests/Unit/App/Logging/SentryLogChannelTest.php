<?php

namespace Tests\Unit\App\Logging;

use App\Logging\Handlers\SkipsExceptionMirrorsHandler;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\NoopHandler;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Sentry\ClientBuilder;
use Sentry\Options;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Tests\TestCases\PublicTestCase;

/**
 * The 'sentry' channel is what turns error-level StructuredLogging records into Sentry issues - Integration::handles()
 * only ever sees uncaught exceptions.
 *
 * The tests that matter here are the end-to-end ones. Every previous way this broke was silent: the channel resolved,
 * the logging call succeeded, and no event was ever sent. Asserting on configuration shape alone reproduces none of
 * that, so these resolve the real DSN-shaped channel against a stub transport and assert an event actually arrives.
 */
#[Group('Logging')]
final class SentryLogChannelTest extends PublicTestCase
{
    /**
     * Name the DSN-shaped channel is registered under. It has to be a real configured channel rather than an
     * on-demand Log::build() one: LogManager::tap() looks the taps up by channel name in logging.channels, so a
     * built channel silently gets none of them.
     */
    private const string DSN_SHAPED_CHANNEL = 'sentry_dsn_shape';

    #[Test]
    public function sentryChannel_givenEmptyDsn_resolvesToValidChannel(): void
    {
        // Arrange - a configured DSN selects the real driver, which is not the case this test guards
        if (!empty(config('sentry.dsn'))) {
            self::markTestSkipped('A Sentry DSN is configured; this test covers the empty-DSN default.');
        }

        // Act
        $sentryConfig = config('logging.channels.sentry');

        // Assert - NullHandler would resolve too, but its handle() returns true, which ends Monolog's handler loop and
        // would swallow every channel listed after sentry in a stack
        self::assertSame(NoopHandler::class, $sentryConfig['handler'] ?? null);
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
     * The regression test for the whole feature: with a DSN configured, an error-level record must actually reach the
     * transport. A buffering tap in front of SentryHandler breaks exactly this while leaving every other assertion in
     * this file green, because SentryHandler::handleBatch() compares a Monolog 3 Level enum as an integer and drops
     * every record.
     */
    #[Test]
    public function sentryChannel_givenConfiguredDsn_sendsErrorRecordToSentry(): void
    {
        // Arrange
        $transport = $this->bindStubHub();

        // Act
        $this->sentryChannel()
            ->error('ProcessCombatLogSegmentsLogging::handleSegmentsNotAvailable', ['runId' => 42015954]);

        // Assert
        self::assertCount(1, $transport->capturedEvents, 'The sentry channel must actually send the record.');
        self::assertSame(
            'ProcessCombatLogSegmentsLogging::handleSegmentsNotAvailable',
            $transport->capturedEvents[0]->getMessage(),
        );
    }

    #[Test]
    public function sentryChannel_givenConfiguredDsn_ignoresRecordsBelowError(): void
    {
        // Arrange
        $transport = $this->bindStubHub();

        // Act
        $this->sentryChannel()->warning('Something suspicious but not actionable.');

        // Assert
        self::assertCount(0, $transport->capturedEvents);
    }

    #[Test]
    public function sentryChannel_givenConfiguredDsn_appliesTheExceptionMirrorFilter(): void
    {
        // Arrange
        $transport = $this->bindStubHub();

        // Act
        $this->sentryChannel()->error('HandlerLogging::uncaughtException', [
            'exceptionClass'         => 'RuntimeException',
            'reportedByErrorTracker' => true,
        ]);

        // Assert
        self::assertCount(0, $transport->capturedEvents, 'A record the SDK already reported must not be sent again.');
    }

    /**
     * Guards the stack-bubbling trap: Monolog stops calling handlers as soon as one returns true, so a channel using
     * NullHandler ahead of sentry in a stack silently swallows it. Production runs LOG_CHANNEL=stack with discord
     * listed before sentry, and discord resolves to a no-op handler whenever no webhook is configured.
     */
    #[Test]
    public function sentryChannel_givenAPrecedingNoOpChannelInAStack_stillReceivesTheRecord(): void
    {
        // Arrange
        $transport = $this->bindStubHub();

        $this->sentryChannel();
        Log::forgetChannel('discord');

        // Act
        Log::stack(['discord', self::DSN_SHAPED_CHANNEL])->error('Errors must survive a no-op channel ahead of sentry.');

        // Assert
        self::assertCount(1, $transport->capturedEvents);
    }

    #[Test]
    public function sentryChannel_givenConfiguredDsn_wrapsHandlerInTheMirrorFilter(): void
    {
        // Act
        $taps = $this->configuredSentryChannel()['tap'] ?? [];

        // Assert - asserted against the real config file, not a copy, so removing the tap fails this test
        self::assertContains(SkipsExceptionMirrorsHandler::class, array_map(
            static fn(string $tap): string => sprintf('%sHandler', explode(':', $tap)[0]),
            $taps,
        ));
    }

    /**
     * Asserted against the configuration rather than a resolved logger on purpose: phpunit.xml pins LOG_CHANNEL=daily,
     * so resolving the default channel would not exercise any of the stacks.
     *
     * Scoped to the stacks this repository declares. Laravel merges the framework's own logging config in, which
     * contributes a 'stack' channel built from LOG_STACK - that one lives in the deployment environment (production
     * runs LOG_CHANNEL=stack), so it cannot be satisfied from here.
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

    private function sentryChannel(): LoggerInterface
    {
        config([sprintf('logging.channels.%s', self::DSN_SHAPED_CHANNEL) => $this->configuredSentryChannel()]);
        Log::forgetChannel(self::DSN_SHAPED_CHANNEL);

        return Log::channel(self::DSN_SHAPED_CHANNEL);
    }

    /**
     * Re-evaluates config/logging.php with a DSN present so the tests run against the branch production uses, rather
     * than a hand-written copy of it that would stay green if the real config changed.
     *
     * @return array<string, mixed>
     */
    private function configuredSentryChannel(): array
    {
        $originalEnv    = $_ENV['SENTRY_LARAVEL_DSN'] ?? null;
        $originalServer = $_SERVER['SENTRY_LARAVEL_DSN'] ?? null;

        $_ENV['SENTRY_LARAVEL_DSN'] = $_SERVER['SENTRY_LARAVEL_DSN'] = 'https://publickey@sentry.example.com/1';

        try {
            $loggingConfig = require config_path('logging.php');
        } finally {
            if ($originalEnv === null) {
                unset($_ENV['SENTRY_LARAVEL_DSN']);
            } else {
                $_ENV['SENTRY_LARAVEL_DSN'] = $originalEnv;
            }

            if ($originalServer === null) {
                unset($_SERVER['SENTRY_LARAVEL_DSN']);
            } else {
                $_SERVER['SENTRY_LARAVEL_DSN'] = $originalServer;
            }
        }

        $sentryChannel = $loggingConfig['channels']['sentry'];
        self::assertSame('sentry', $sentryChannel['driver'], 'Expected the DSN branch of the sentry channel.');

        return $sentryChannel;
    }

    /**
     * Binds a Hub backed by an in-memory transport, which is what the 'sentry' log driver resolves out of the
     * container, so nothing leaves the test.
     */
    private function bindStubHub(): CapturingTransport
    {
        $transport = new CapturingTransport();

        $clientBuilder = new ClientBuilder(new Options([
            'dsn'                  => 'https://publickey@sentry.example.com/1',
            'default_integrations' => false,
        ]));
        $clientBuilder->setTransport($transport);

        $this->app->instance(HubInterface::class, new Hub($clientBuilder->getClient()));

        return $transport;
    }
}
