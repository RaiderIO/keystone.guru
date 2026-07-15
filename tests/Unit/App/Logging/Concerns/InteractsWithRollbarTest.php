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
        // Arrange
        Rollbar::destroy();

        // Act
        $loggers = (new TestableRollbarLogging())->resolveDefaultLoggers();

        // Assert
        self::assertCount(1, $loggers);
    }

    #[Test]
    public function getDefaultLoggers_givenRollbarInitialized_includesRollbarLoggerOnce(): void
    {
        // Arrange
        Rollbar::destroy();
        Rollbar::init(['enabled' => false]);

        try {
            // Act
            $loggers = (new TestableRollbarLogging())->resolveDefaultLoggers();

            // Assert
            self::assertCount(2, $loggers);
            self::assertSame(Rollbar::logger(), $loggers[1]);
        } finally {
            Rollbar::destroy();
        }
    }
}
