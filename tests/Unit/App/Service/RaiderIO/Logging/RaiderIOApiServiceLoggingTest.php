<?php

namespace Tests\Unit\App\Service\RaiderIO\Logging;

use App\Service\RaiderIO\Logging\RaiderIOApiServiceLoggingInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\Fixtures\LoggingFixtures;
use Tests\TestCases\PublicTestCase;

/**
 * Guards #3918: the log level is the entire behaviour this issue is about (the sentry log channel
 * alerts on error-level logs - see config/logging.php), so it must be asserted directly against the
 * concrete logging class rather than a mocked interface, which cannot observe it.
 */
#[Group('Logging')]
#[Group('RaiderIOApiServiceLogging')]
final class RaiderIOApiServiceLoggingTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.log_level' => 'debug', 'app.type' => 'local']);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getCombatLogSegmentsForRunNotYetAvailable_givenCalled_logsAtInfoNotError(): void
    {
        // Arrange
        $logManager = LoggingFixtures::createLogManager($this);
        app()->instance('log', $logManager);

        $logManager->expects($this->once())->method('log')->with('INFO');

        /** @var RaiderIOApiServiceLoggingInterface $log */
        $log = app(RaiderIOApiServiceLoggingInterface::class);

        // Act
        $log->getCombatLogSegmentsForRunNotYetAvailable(37830910, 'https://raider.io/segments', '{"statusCode":404}');
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function getCombatLogSegmentsForRunInvalidResponse_givenCalled_logsAtErrorNotInfo(): void
    {
        // Arrange
        $logManager = LoggingFixtures::createLogManager($this);
        app()->instance('log', $logManager);

        $logManager->expects($this->once())->method('log')->with('ERROR');

        /** @var RaiderIOApiServiceLoggingInterface $log */
        $log = app(RaiderIOApiServiceLoggingInterface::class);

        // Act
        $log->getCombatLogSegmentsForRunInvalidResponse(37830910, 'https://raider.io/segments', 'not json');
    }
}
