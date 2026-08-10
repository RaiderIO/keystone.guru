<?php

namespace Tests\Unit\App\Logging;

use App\Logging\Sentry\ScheduledCommandFingerprint;
use Closure;
use Exception;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\Options;
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
     *
     * The exception message embeds Illuminate\Console\Application::formatCommandString()'s full
     * output - the shell-escaped php and artisan binaries prefixed onto the actual command (e.g.
     * `'/usr/local/bin/php' 'artisan' combatlog:detectstaledata`, confirmed against a real run in
     * this app's container) - not just the bare command name, so the fixture must reproduce that
     * shape or a regex bug that captures the whole line (embedding an environment-dependent
     * interpreter path in the fingerprint) would pass unnoticed.
     */
    #[Test]
    public function apply_givenScheduledCommandFailedException_fingerprintsByCommandName(): void
    {
        // Arrange
        $exception = new Exception(
            "Scheduled command ['/usr/local/bin/php' 'artisan' combatlog:detectstaledata] failed with exit code [1].",
        );
        $event = Event::createEvent();
        $hint  = EventHint::fromArray(['exception' => $exception]);

        // Act
        $result = ScheduledCommandFingerprint::apply($event, $hint);

        // Assert - the php/artisan binary tokens are stripped, leaving just the command
        self::assertSame(['schedule-run-command-failed', 'combatlog:detectstaledata'], $result->getFingerprint());
    }

    /**
     * A scheduled command with parameters (Schedule::command()'s compileParameters()) appends them
     * to the command name space-separated, e.g. `dungeonroute:touch teamId=5` - the fingerprint must
     * still isolate this from the binary prefix.
     */
    #[Test]
    public function apply_givenScheduledCommandWithParametersFailedException_fingerprintsByCommandAndParameters(): void
    {
        // Arrange
        $exception = new Exception(
            "Scheduled command ['/usr/local/bin/php' 'artisan' dungeonroute:touch teamId=5] failed with exit code [1].",
        );
        $event = Event::createEvent();
        $hint  = EventHint::fromArray(['exception' => $exception]);

        // Act
        $result = ScheduledCommandFingerprint::apply($event, $hint);

        // Assert
        self::assertSame(['schedule-run-command-failed', 'dungeonroute:touch teamId=5'], $result->getFingerprint());
    }

    #[Test]
    public function apply_givenDifferentScheduledCommandFailedException_fingerprintsSeparately(): void
    {
        // Arrange - proves two different commands don't collapse into the same fingerprint
        $firstEvent = ScheduledCommandFingerprint::apply(Event::createEvent(), EventHint::fromArray([
            'exception' => new Exception(
                "Scheduled command ['/usr/local/bin/php' 'artisan' combatlog:detectstaledata] failed with exit code [1].",
            ),
        ]));
        $secondEvent = ScheduledCommandFingerprint::apply(Event::createEvent(), EventHint::fromArray([
            'exception' => new Exception(
                "Scheduled command ['/usr/local/bin/php' 'artisan' patreon:refreshmembers] failed with exit code [1].",
            ),
        ]));

        // Assert
        self::assertNotSame($firstEvent->getFingerprint(), $secondEvent->getFingerprint());
    }

    #[Test]
    public function apply_givenUnrelatedException_leavesFingerprintUntouched(): void
    {
        // Arrange
        $event = Event::createEvent();
        $hint  = EventHint::fromArray(['exception' => new RuntimeException('Something else broke entirely')]);

        // Act
        $result = ScheduledCommandFingerprint::apply($event, $hint);

        // Assert - Sentry's default (trace-based) grouping applies; no custom fingerprint set
        self::assertSame([], $result->getFingerprint());
    }

    #[Test]
    public function apply_givenNoException_returnsEventUnmodified(): void
    {
        // Arrange
        $event = Event::createEvent();

        // Act
        $result = ScheduledCommandFingerprint::apply($event, null);

        // Assert
        self::assertSame($event, $result);
        self::assertSame([], $result->getFingerprint());
    }

    /**
     * `php artisan config:cache` var_exports every config value and throws a LogicException on anything
     * non-serializable, so a Closure in this config file breaks config caching (which the infrastructure repository
     * runs on deploy). An array callable survives var_export; first-class callable syntax would not.
     */
    #[Test]
    public function beforeSend_givenTheConfig_isSerializableForConfigCaching(): void
    {
        // Act
        $beforeSend = config('sentry.before_send');

        // Assert
        self::assertNotInstanceOf(Closure::class, $beforeSend, 'A Closure here breaks `php artisan config:cache`.');
        self::assertSame([ScheduledCommandFingerprint::class, 'apply'], $beforeSend);
        // The exact round-trip ConfigCacheCommand performs - a Closure dies here on Closure::__set_state()
        self::assertSame($beforeSend, eval(sprintf('return %s;', var_export($beforeSend, true))));
    }

    /**
     * Being var_export-able is not the same as being accepted by the SDK - Sentry's Options resolver gates
     * `before_send` on `is_callable`, which rejects an array callable pointing at a non-static method.
     */
    #[Test]
    public function beforeSend_givenTheConfig_isAcceptedBySentryOptions(): void
    {
        // Act
        $options = new Options(['before_send' => config('sentry.before_send')]);

        // Assert
        self::assertSame(config('sentry.before_send'), $options->getBeforeSendCallback());
    }
}
