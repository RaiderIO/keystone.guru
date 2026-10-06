<?php

namespace Tests\Unit\App\Logging;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Sentry\ClientBuilder;
use Sentry\Options;
use Sentry\State\Hub;
use Sentry\State\HubInterface;

/**
 * Resolves the real 'sentry' log channel as production configures it (DSN present) against an in-memory transport.
 */
trait ResolvesDsnShapedSentryChannel
{
    /**
     * Name the DSN-shaped channel is registered under. It has to be a real configured channel rather than an
     * on-demand Log::build() one: LogManager::tap() looks the taps up by channel name in logging.channels, so a
     * built channel silently gets none of them.
     */
    private const string DSN_SHAPED_CHANNEL = 'sentry_dsn_shape';

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
