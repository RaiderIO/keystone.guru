<?php

namespace App\Console\Commands\CombatLog;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Models\CombatLog\ParsedCombatLog;

class PruneParsedCombatLogsCommand extends SchedulerCommand
{
    /**
     * Retention is derived from the polling window rather than hard-coded, so pruning never outpaces
     * `combatlog:pollruns` even if the window is widened again (#4035) - see #4062.
     */
    private const int RETENTION_DAYS_FLOOR = 30;

    /**
     * 4x is a safety margin, not a measured requirement: `combatlog:pollruns` can never re-match a
     * row once it's outside the polling window, so retention only needs to exceed that window by
     * *some* margin. 4x keeps the table at roughly its current steady-state size (#4062 measured
     * ~13.3k rows/day; retention days x that rate is the table size) while leaving room for the
     * window to widen again without pruning starting to outrun it, the way #4035 already widened it
     * once (1 -> 7 days).
     */
    private const int RETENTION_DAYS_WINDOW_MULTIPLIER = 4;

    protected $signature = 'combatlog:pruneparsedlogs';

    protected $description = 'Deletes parsed_combat_logs rows older than the polling window, which can never be matched again.';

    public function handle(): int
    {
        return $this->trackTime(function (): void {
            $windowDays    = (int)config('keystoneguru.raider_io.combat_log_polling.completed_at_window_days');
            $retentionDays = max(self::RETENTION_DAYS_FLOOR, $windowDays * self::RETENTION_DAYS_WINDOW_MULTIPLIER);
            $cutoff        = now()->subDays($retentionDays);
            $batchSize     = 10000;
            $totalDeleted  = 0;

            do {
                $deleted = ParsedCombatLog::query()
                    ->where('created_at', '<', $cutoff)
                    ->limit($batchSize)
                    ->delete();

                $totalDeleted += $deleted;
            } while ($deleted === $batchSize);

            $this->info(sprintf('Pruned %d parsed_combat_logs records older than %d days.', $totalDeleted, $retentionDays));
        });
    }
}
