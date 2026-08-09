<?php

namespace Tests\Unit\App\Logging;

use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Sentry\Event;
use Sentry\EventHint;
use Tests\TestCases\PublicTestCase;

/**
 * Sentry's release and environment are derived from what the application already knows rather than requiring two more
 * deployment variables. Both fall back silently when wrong - a stale release breaks regression detection and the
 * 'Fixes <SHORT-ID>' auto-resolve, and a missing environment makes staging and production indistinguishable.
 */
#[Group('Logging')]
final class SentryConfigTest extends PublicTestCase
{
    #[Test]
    public function release_givenNoEnvironmentOverride_fallsBackToTheVersionFile(): void
    {
        // Arrange - getenv() rather than env(), which larastan forbids outside the config directory
        if (!empty(getenv('SENTRY_RELEASE'))) {
            self::markTestSkipped('SENTRY_RELEASE is set; this test covers the fallback.');
        }

        // Act
        $release = config('sentry.release');

        // Assert
        self::assertSame(trim((string)file_get_contents(base_path('version'))), $release);
        self::assertNotEmpty($release, 'Sentry issues without a release cannot be tied to a deploy.');
    }

    #[Test]
    public function environment_givenNoEnvironmentOverride_fallsBackToAppType(): void
    {
        // Arrange - getenv() rather than env(), which larastan forbids outside the config directory
        if (!empty(getenv('SENTRY_ENVIRONMENT'))) {
            self::markTestSkipped('SENTRY_ENVIRONMENT is set; this test covers the fallback.');
        }

        // Act
        $environment = config('sentry.environment');

        // Assert - app.type reads the same APP_TYPE the fallback does
        self::assertSame(config('app.type'), $environment);
        self::assertNotEmpty($environment, 'Without an environment, staging and production issues are indistinguishable.');
    }

    /**
     * Guards #3902: Laravel's ScheduleRunCommand throws an identically-shaped Exception (same
     * message pattern, same vendor-code stack trace) for every failing scheduled command, so
     * Sentry's default trace-based grouping aggregated failures from unrelated commands
     * (combatlog:detectstaledata, patreon:refreshmembers, ...) into one issue. before_send must
     * fingerprint on the command name so each gets its own issue.
     */
    #[Test]
    public function beforeSend_givenScheduledCommandFailedException_fingerprintsByCommandName(): void
    {
        // Arrange
        $beforeSend = config('sentry.before_send');
        $exception  = new Exception('Scheduled command [combatlog:detectstaledata] failed with exit code [1].');
        $event      = Event::createEvent();
        $hint       = EventHint::fromArray(['exception' => $exception]);

        // Act
        $result = $beforeSend($event, $hint);

        // Assert
        self::assertSame(['schedule-run-command-failed', 'combatlog:detectstaledata'], $result->getFingerprint());
    }

    #[Test]
    public function beforeSend_givenDifferentScheduledCommandFailedException_fingerprintsSeparately(): void
    {
        // Arrange - proves two different commands don't collapse into the same fingerprint
        $beforeSend = config('sentry.before_send');

        $firstEvent = $beforeSend(Event::createEvent(), EventHint::fromArray([
            'exception' => new Exception('Scheduled command [combatlog:detectstaledata] failed with exit code [1].'),
        ]));
        $secondEvent = $beforeSend(Event::createEvent(), EventHint::fromArray([
            'exception' => new Exception('Scheduled command [patreon:refreshmembers] failed with exit code [1].'),
        ]));

        // Assert
        self::assertNotSame($firstEvent->getFingerprint(), $secondEvent->getFingerprint());
    }

    #[Test]
    public function beforeSend_givenUnrelatedException_leavesFingerprintUntouched(): void
    {
        // Arrange
        $beforeSend = config('sentry.before_send');
        $event      = Event::createEvent();
        $hint       = EventHint::fromArray(['exception' => new RuntimeException('Something else broke entirely')]);

        // Act
        $result = $beforeSend($event, $hint);

        // Assert - Sentry's default (trace-based) grouping applies; no custom fingerprint set
        self::assertSame([], $result->getFingerprint());
    }

    #[Test]
    public function beforeSend_givenNoException_returnsEventUnmodified(): void
    {
        // Arrange
        $beforeSend = config('sentry.before_send');
        $event      = Event::createEvent();

        // Act
        $result = $beforeSend($event, null);

        // Assert
        self::assertSame($event, $result);
        self::assertSame([], $result->getFingerprint());
    }
}
