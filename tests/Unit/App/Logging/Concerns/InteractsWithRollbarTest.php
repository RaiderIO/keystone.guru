<?php

namespace Tests\Unit\App\Logging\Concerns;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Rollbar\Rollbar;
use Tests\TestCases\PublicTestCase;

#[Group('Logging')]
#[Group('InteractsWithRollbar')]
class InteractsWithRollbarTest extends PublicTestCase
{
    #[Test]
    public function getDefaultLoggers_givenRollbarNotInitialized_returnsDefaultLoggersWithoutThrowing(): void
    {
        // Arrange - AppServiceProvider::boot() already called the real Rollbar::init() during this test's own app
        // boot. Rollbar::$logger is a process-wide static that every other test's app boot also touches, so the
        // real (fully configured) logger is saved and restored rather than left destroyed or replaced with a
        // partial config - either would get baked into Rollbar's PHP error-handler chain for the rest of the suite.
        $originalLogger = Rollbar::logger();
        Rollbar::destroy();

        try {
            // Act
            $loggers = (new TestableRollbarLogging())->resolveDefaultLoggers();

            // Assert
            self::assertCount(1, $loggers);
        } finally {
            if ($originalLogger !== null) {
                Rollbar::init($originalLogger);
            }
        }
    }

    #[Test]
    public function getDefaultLoggers_givenRollbarInitialized_includesRollbarLoggerOnce(): void
    {
        // Arrange - AppServiceProvider::boot() already called Rollbar::init() during this test's own app boot
        self::assertNotNull(Rollbar::logger(), 'Rollbar::init() should have run during app boot.');

        // Act
        $loggers = (new TestableRollbarLogging())->resolveDefaultLoggers();

        // Assert
        self::assertCount(2, $loggers);
        self::assertSame(Rollbar::logger(), $loggers[1]);
    }
}
