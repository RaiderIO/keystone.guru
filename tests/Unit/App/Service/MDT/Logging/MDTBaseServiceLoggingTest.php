<?php

namespace Tests\Unit\App\Service\MDT\Logging;

use App\Service\MDT\Logging\MDTBaseServiceLogging;
use Illuminate\Support\Facades\Log;
use Monolog\Level;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\Fixtures\LoggingFixtures;
use Tests\TestCases\PublicTestCase;

/**
 * Guards the levels behind #3906: which of the two decode-failure log events alerts (error - Discord +
 * Sentry) and which one only shows up in the logs (warning) is the entire point of that fix, and the
 * service-level tests can only assert which event was raised, not at what level it lands.
 */
#[Group('Logging')]
#[Group('MDTBaseServiceLogging')]
class MDTBaseServiceLoggingTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function decodeFailed_givenAnyString_logsAtErrorLevel(): void
    {
        // Arrange
        config(['app.log_level' => 'debug']);

        $logManager = LoggingFixtures::createLogManager($this);
        Log::swap($logManager);

        $logManager
            ->expects($this->once())
            ->method('log')
            ->with(Level::Error->getName(), $this->anything(), $this->anything());

        // Act
        (new MDTBaseServiceLogging())->decodeFailed('!abc', 'RuntimeException', 'Failed to decompress data');

        // Assert
        // Already checked by the mock expectation
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function decodeInvalidStringFailed_givenAnyString_logsAtWarningLevel(): void
    {
        // Arrange
        config(['app.log_level' => 'debug']);

        $logManager = LoggingFixtures::createLogManager($this);
        Log::swap($logManager);

        $logManager
            ->expects($this->once())
            ->method('log')
            ->with(Level::Warning->getName(), $this->anything(), $this->anything());

        // Act
        (new MDTBaseServiceLogging())->decodeInvalidStringFailed('garbage', 'RuntimeException', 'Invalid prefix');

        // Assert
        // Already checked by the mock expectation
    }
}
