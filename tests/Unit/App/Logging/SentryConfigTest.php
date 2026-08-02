<?php

namespace Tests\Unit\App\Logging;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
}
