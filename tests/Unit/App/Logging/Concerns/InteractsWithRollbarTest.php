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

    /**
     * #4483 - Rollbar::logger() being non-null (asserted above) is not the same as it actually reporting: it is
     * disabled() whenever app()->runningUnitTests() (AppServiceProvider::boot()'s 'enabled' config), so report()
     * short-circuits before it ever reaches the network. Without this, a suite run on a machine with a real
     * ROLLBAR_SERVER_ACCESS_TOKEN in its .env would still send test failures to the shared Rollbar project.
     */
    #[Test]
    public function logger_givenTestRun_isDisabled(): void
    {
        // Arrange
        $logger = Rollbar::logger();
        self::assertNotNull($logger, 'Rollbar::init() should have run during app boot.');

        // Act
        $disabled = $logger->disabled();

        // Assert
        self::assertTrue($disabled, 'Rollbar must not report during the test suite.');
    }
}
