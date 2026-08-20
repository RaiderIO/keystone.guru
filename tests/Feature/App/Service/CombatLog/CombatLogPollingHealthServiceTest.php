<?php

namespace Tests\Feature\App\Service\CombatLog;

use App\Service\CombatLog\CombatLogPollingHealthService;
use App\Service\CombatLog\CombatLogPollingHealthServiceInterface;
use App\Service\CombatLog\Enums\CombatLogPollingFailureReason;
use App\Service\CombatLog\Logging\CombatLogPollingHealthServiceLoggingInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('CombatLogPollingHealthService')]
final class CombatLogPollingHealthServiceTest extends PublicTestCase
{
    private CombatLogPollingHealthServiceLoggingInterface&MockObject $log;

    private CombatLogPollingHealthServiceInterface $service;

    /**
     * @throws Exception
     */
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->log     = $this->createMockPublic(CombatLogPollingHealthServiceLoggingInterface::class);
        $this->service = new CombatLogPollingHealthService($this->log);
    }

    #[\Override]
    protected function tearDown(): void
    {
        Cache::flush();
        Carbon::setTestNow(null);

        parent::tearDown();
    }

    #[Test]
    public function getSummary_givenRecordedOutcomes_returnsCountsForThatHour(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::parse('2026-08-20 14:30:00'));
        $this->service->recordDispatched();
        $this->service->recordDispatched();
        $this->service->recordSucceeded();
        $this->service->recordFailure(CombatLogPollingFailureReason::ParseFailed);

        // Act
        $summary = $this->service->getSummary(Carbon::parse('2026-08-20 14:59:59'));

        // Assert
        $this->assertSame(2, $summary->dispatched);
        $this->assertSame(1, $summary->succeeded);
        $this->assertSame(1, $summary->getTotalFailures());
        $this->assertSame(1, $summary->getFailureCount(CombatLogPollingFailureReason::ParseFailed));
        $this->assertSame(0, $summary->getFailureCount(CombatLogPollingFailureReason::SearchApiError));
        $this->assertEqualsWithDelta(0.5, $summary->getFailureRate(), 0.001);
        $this->assertFalse($summary->isEmpty());
    }

    #[Test]
    public function getSummary_givenOutcomesRecordedInAnotherHour_returnsEmptySummary(): void
    {
        // Arrange - counters are bucketed per hour so a bad hour cannot bleed into the next one
        Carbon::setTestNow(Carbon::parse('2026-08-20 14:30:00'));
        $this->service->recordDispatched();

        // Act
        $summary = $this->service->getSummary(Carbon::parse('2026-08-20 15:30:00'));

        // Assert
        $this->assertTrue($summary->isEmpty());
        $this->assertSame(0.0, $summary->getFailureRate());
    }

    #[Test]
    public function reportSummary_givenFailuresAboveBothThresholds_reportsDegraded(): void
    {
        // Arrange
        config([
            'keystoneguru.raider_io.combat_log_polling.health.min_failures'     => 5,
            'keystoneguru.raider_io.combat_log_polling.health.min_failure_rate' => 0.5,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-08-20 14:30:00'));
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordDispatched();
        }
        for ($i = 0; $i < 6; $i++) {
            $this->service->recordFailure(CombatLogPollingFailureReason::SegmentsApiError);
        }

        $this->log->expects($this->once())->method('reportSummaryDegraded');
        $this->log->expects($this->never())->method('reportSummaryHealthy');

        // Act
        $degraded = $this->service->reportSummary($this->service->getSummary(Carbon::now()));

        // Assert
        $this->assertTrue($degraded);
    }

    #[Test]
    public function reportSummary_givenHighRateButFewFailures_reportsHealthy(): void
    {
        // Arrange - two failures out of two runs is noise, not an outage worth waking up for
        config([
            'keystoneguru.raider_io.combat_log_polling.health.min_failures'     => 5,
            'keystoneguru.raider_io.combat_log_polling.health.min_failure_rate' => 0.5,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-08-20 14:30:00'));
        $this->service->recordDispatched();
        $this->service->recordDispatched();
        $this->service->recordFailure(CombatLogPollingFailureReason::SegmentsUnavailable);
        $this->service->recordFailure(CombatLogPollingFailureReason::SegmentsUnavailable);

        $this->log->expects($this->once())->method('reportSummaryHealthy');
        $this->log->expects($this->never())->method('reportSummaryDegraded');

        // Act
        $degraded = $this->service->reportSummary($this->service->getSummary(Carbon::now()));

        // Assert
        $this->assertFalse($degraded);
    }

    #[Test]
    public function reportSummary_givenManyFailuresButLowRate_reportsHealthy(): void
    {
        // Arrange - a busy hour that mostly worked is not degraded, however many failures it holds
        config([
            'keystoneguru.raider_io.combat_log_polling.health.min_failures'     => 5,
            'keystoneguru.raider_io.combat_log_polling.health.min_failure_rate' => 0.5,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-08-20 14:30:00'));
        for ($i = 0; $i < 100; $i++) {
            $this->service->recordDispatched();
        }
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordFailure(CombatLogPollingFailureReason::ParseFailed);
        }

        $this->log->expects($this->once())->method('reportSummaryHealthy');
        $this->log->expects($this->never())->method('reportSummaryDegraded');

        // Act
        $degraded = $this->service->reportSummary($this->service->getSummary(Carbon::now()));

        // Assert
        $this->assertFalse($degraded);
    }

    #[Test]
    public function getFailureRate_givenFailuresWithoutDispatchedRuns_returnsOne(): void
    {
        // Arrange - search API errors happen before any run is picked, so nothing was dispatched
        Carbon::setTestNow(Carbon::parse('2026-08-20 14:30:00'));
        $this->service->recordFailure(CombatLogPollingFailureReason::SearchApiError);
        $this->service->recordFailure(CombatLogPollingFailureReason::SearchApiError);

        // Act
        $summary = $this->service->getSummary(Carbon::now());

        // Assert
        $this->assertSame(1.0, $summary->getFailureRate());
    }
}
