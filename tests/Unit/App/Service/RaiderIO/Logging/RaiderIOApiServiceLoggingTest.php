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
     * Was asserted at error until #4173: a single bad response from Raider.IO is transient, recovers
     * on the next poll, and costs one run that the poll after it replaces on its own. The volume of
     * them is what matters, and combatlog:reportpollinghealth is what reports on that, at error.
     *
     * @throws Exception
     */
    #[Test]
    public function getCombatLogSegmentsForRunInvalidResponse_givenCalled_logsAtWarningNotError(): void
    {
        // Arrange
        $logManager = LoggingFixtures::createLogManager($this);
        app()->instance('log', $logManager);

        $logManager->expects($this->once())->method('log')->with('WARNING');

        /** @var RaiderIOApiServiceLoggingInterface $log */
        $log = app(RaiderIOApiServiceLoggingInterface::class);

        // Act
        $log->getCombatLogSegmentsForRunInvalidResponse(37830910, 'https://raider.io/segments', 'not json');
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function searchAdvancedRunsInvalidResponse_givenCalled_logsAtWarningNotError(): void
    {
        // Arrange
        $logManager = LoggingFixtures::createLogManager($this);
        app()->instance('log', $logManager);

        $logManager->expects($this->once())->method('log')->with('WARNING');

        /** @var RaiderIOApiServiceLoggingInterface $log */
        $log = app(RaiderIOApiServiceLoggingInterface::class);

        // Act
        $log->searchAdvancedRunsInvalidResponse('https://raider.io/api/search-advanced', '<html>502 Bad gateway</html>');
    }
}
