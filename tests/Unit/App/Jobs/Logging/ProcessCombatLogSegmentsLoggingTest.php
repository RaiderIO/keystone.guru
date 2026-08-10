<?php

namespace Tests\Unit\App\Jobs\Logging;

use App\Jobs\Logging\ProcessCombatLogSegmentsLoggingInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\Fixtures\LoggingFixtures;
use Tests\TestCases\PublicTestCase;

/**
 * Guards #3918: handleSegmentsNotAvailable() was downgraded from error to info so it stops paging
 * the sentry log channel (config/logging.php alerts on error level) for an expected, recurring
 * state - asserted directly against the concrete logging class, since a mocked interface (as used
 * elsewhere in this job's own test suite) can't observe the level.
 */
#[Group('Logging')]
#[Group('ProcessCombatLogSegmentsLogging')]
final class ProcessCombatLogSegmentsLoggingTest extends PublicTestCase
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
    public function handleSegmentsNotAvailable_givenCalled_logsAtInfoNotError(): void
    {
        // Arrange
        $logManager = LoggingFixtures::createLogManager($this);
        app()->instance('log', $logManager);

        $logManager->expects($this->once())->method('log')->with('INFO');

        /** @var ProcessCombatLogSegmentsLoggingInterface $log */
        $log = app(ProcessCombatLogSegmentsLoggingInterface::class);

        // Act
        $log->handleSegmentsNotAvailable(37830910);
    }
}
