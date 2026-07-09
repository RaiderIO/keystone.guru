<?php

namespace Tests\Unit\App\Logging;

use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Regression coverage for #3445: when APP_LOG_DISCORD_WEBHOOK is empty (the local/testing default),
 * the 'discord' channel must still resolve to a valid channel so the 'scheduler' stack that includes
 * it can be built without emitting "Undefined array key \"driver\"" warnings or falling back to the
 * emergency logger.
 */
#[Group('Logging')]
final class SchedulerLogChannelTest extends PublicTestCase
{
    #[Test]
    public function discordChannel_givenEmptyWebhook_resolvesToValidChannel(): void
    {
        // Arrange - a configured 'url' means a webhook is set, which is not the case this test guards
        $discordConfig = config('logging.channels.discord');
        if (array_key_exists('url', $discordConfig)) {
            self::markTestSkipped('A discord webhook is configured; this test covers the empty-webhook default.');
        }

        // Act & Assert
        self::assertArrayHasKey('driver', $discordConfig, 'discord must be a valid channel even without a webhook.');
    }

    #[Test]
    public function schedulerChannel_givenEmptyWebhook_logsWithoutEmittingPhpErrors(): void
    {
        // Arrange - force a fresh resolution so channel resolution (and any warning) actually happens
        Log::forgetChannel('scheduler');
        Log::forgetChannel('discord');

        $capturedErrors = [];
        set_error_handler(static function (int $severity, string $message) use (&$capturedErrors): bool {
            $capturedErrors[] = $message;

            return true;
        });

        try {
            // Act
            Log::channel('scheduler')->info('Regression check for #3445 - scheduler channel must be a safe no-op.');
        } finally {
            restore_error_handler();
        }

        // Assert
        self::assertSame(
            [],
            array_values(array_unique($capturedErrors)),
            'Logging to the scheduler channel must not emit PHP warnings when the discord webhook is empty.',
        );
    }
}
