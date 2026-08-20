<?php

namespace Tests\Unit\App\Service\CombatLog\Logging;

use App\Service\CombatLog\Logging\CombatLogPollingHealthServiceLoggingInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\Fixtures\LoggingFixtures;
use Tests\TestCases\PublicTestCase;

/**
 * The whole point of #4173 is which of these two pages and which does not: every individual polling
 * failure was downgraded below error, and this aggregate took over as the one error-level signal.
 * The level is the behaviour, so it is asserted against the concrete logging class - a mocked
 * interface cannot observe it (the sentry log channel alerts on error level, see config/logging.php).
 */
#[Group('Logging')]
#[Group('CombatLogPollingHealthServiceLogging')]
final class CombatLogPollingHealthServiceLoggingTest extends PublicTestCase
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
    public function reportSummaryDegraded_givenCalled_logsAtError(): void
    {
        // Arrange
        $logManager = LoggingFixtures::createLogManager($this);
        app()->instance('log', $logManager);

        $logManager->expects($this->once())->method('log')->with('ERROR');

        /** @var CombatLogPollingHealthServiceLoggingInterface $log */
        $log = app(CombatLogPollingHealthServiceLoggingInterface::class);

        // Act
        $log->reportSummaryDegraded('2026-08-20-14', 40, 5, 35, 0.875, ['parse_failed' => 35]);
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function reportSummaryHealthy_givenCalled_logsAtInfoNotError(): void
    {
        // Arrange
        $logManager = LoggingFixtures::createLogManager($this);
        app()->instance('log', $logManager);

        $logManager->expects($this->once())->method('log')->with('INFO');

        /** @var CombatLogPollingHealthServiceLoggingInterface $log */
        $log = app(CombatLogPollingHealthServiceLoggingInterface::class);

        // Act
        $log->reportSummaryHealthy('2026-08-20-14', 40, 38, 2, 0.05, ['parse_failed' => 2]);
    }
}
