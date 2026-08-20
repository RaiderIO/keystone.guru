<?php

namespace Tests\Feature\Console\Commands\CombatLog;

use App\Service\CombatLog\CombatLogPollingHealthServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogPollingHealthSummary;
use App\Service\CombatLog\Enums\CombatLogPollingFailureReason;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('CombatLog')]
final class ReportCombatLogPollingHealthCommandTest extends PublicTestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        Carbon::setTestNow(null);

        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenDefaultOptions_reportsOnTheWindowEndingInThePreviousHour(): void
    {
        // Arrange - the current hour is still being written to, so it is the previous one that is reported on
        Carbon::setTestNow(Carbon::parse('2026-08-20 15:10:00'));

        $healthService = $this->createMockPublic(CombatLogPollingHealthServiceInterface::class);
        $healthService->expects($this->once())
            ->method('getSummary')
            ->with($this->callback(static fn(Carbon $endHour): bool => $endHour->format('Y-m-d-H') === '2026-08-20-14'))
            ->willReturn($this->makeSummary(dispatched: 10, failures: 2));
        $healthService->expects($this->once())->method('reportSummary')->willReturn(false);
        app()->instance(CombatLogPollingHealthServiceInterface::class, $healthService);

        // Act + Assert
        $this->artisan('combatlog:reportpollinghealth')->assertSuccessful();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenNothingPolledInThatWindow_doesNotReport(): void
    {
        // Arrange - a window in which nothing happened has nothing to say, healthy or otherwise
        $healthService = $this->createMockPublic(CombatLogPollingHealthServiceInterface::class);
        $healthService->method('getSummary')->willReturn($this->makeSummary(dispatched: 0, failures: 0));
        $healthService->expects($this->never())->method('reportSummary');
        app()->instance(CombatLogPollingHealthServiceInterface::class, $healthService);

        // Act + Assert
        $this->artisan('combatlog:reportpollinghealth')->assertSuccessful();
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenHoursAgoOption_reportsOnTheWindowEndingInThatHour(): void
    {
        // Arrange
        Carbon::setTestNow(Carbon::parse('2026-08-20 15:10:00'));

        $healthService = $this->createMockPublic(CombatLogPollingHealthServiceInterface::class);
        $healthService->expects($this->once())
            ->method('getSummary')
            ->with($this->callback(static fn(Carbon $endHour): bool => $endHour->format('Y-m-d-H') === '2026-08-20-12'))
            ->willReturn($this->makeSummary(dispatched: 10, failures: 9));
        $healthService->method('reportSummary')->willReturn(true);
        app()->instance(CombatLogPollingHealthServiceInterface::class, $healthService);

        // Act + Assert
        $this->artisan('combatlog:reportpollinghealth', ['--hours-ago' => 3])->assertSuccessful();
    }

    private function makeSummary(int $dispatched, int $failures): CombatLogPollingHealthSummary
    {
        $failuresByReason = [];
        foreach (CombatLogPollingFailureReason::cases() as $reason) {
            $failuresByReason[$reason->value] = 0;
        }
        $failuresByReason[CombatLogPollingFailureReason::ParseFailed->value] = $failures;

        return new CombatLogPollingHealthSummary(
            hour:             '2026-08-20-14',
            dispatched:       $dispatched,
            succeeded:        max($dispatched - $failures, 0),
            failuresByReason: $failuresByReason,
        );
    }
}
