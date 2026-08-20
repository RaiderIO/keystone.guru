<?php

namespace App\Console\Commands\CombatLog;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Service\CombatLog\CombatLogPollingHealthServiceInterface;
use Illuminate\Support\Carbon;

/**
 * Reports on what the previous hour of combat log polling amounted to, and pages only when a
 * substantial share of it failed.
 *
 * Individual failures - Raider.IO gateway errors, missing segments, unparsable logs - are logged
 * below error level on purpose: each one costs a single run that the next poll replaces on its own,
 * and reporting them one by one buries the case that actually needs attention, which is a lot of
 * them at once (#4173).
 */
class ReportCombatLogPollingHealthCommand extends SchedulerCommand
{
    protected $signature = 'combatlog:reportpollinghealth {--hours-ago=1 : Which hour to report on, counting back from now}';

    protected $description = 'Reports the previous hour of combat log polling, at error level if a substantial share of it failed.';

    public function handle(CombatLogPollingHealthServiceInterface $healthService): int
    {
        return $this->trackTime(function () use ($healthService): void {
            // The hour before this one by default: the current one is still being written to, and
            // reporting on a half-finished hour would page on a spike that is about to even out.
            $hour    = Carbon::now()->subHours(max(1, (int)$this->option('hours-ago')));
            $summary = $healthService->getSummary($hour);

            if ($summary->isEmpty()) {
                $this->info(sprintf('combatlog:reportpollinghealth — %s | nothing polled', $summary->hour));

                return;
            }

            $degraded = $healthService->reportSummary($summary);

            $this->info(sprintf(
                'combatlog:reportpollinghealth — %s | dispatched=%d succeeded=%d failed=%d rate=%.2f degraded=%s | %s',
                $summary->hour,
                $summary->dispatched,
                $summary->succeeded,
                $summary->getTotalFailures(),
                $summary->getFailureRate(),
                $degraded ? 'yes' : 'no',
                implode(', ', array_map(
                    static fn(string $reason, int $count): string => sprintf('%s=%d', $reason, $count),
                    array_keys($summary->failuresByReason),
                    $summary->failuresByReason,
                )),
            ));
        });
    }
}
