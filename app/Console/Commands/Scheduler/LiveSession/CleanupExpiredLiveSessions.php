<?php

namespace App\Console\Commands\Scheduler\LiveSession;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Models\LiveSession\LiveSession;

class CleanupExpiredLiveSessions extends SchedulerCommand
{
    protected $signature = 'livesession:cleanup-expired';

    protected $description = 'Deletes relation data (overpulled enemies, enemy states, player positions, combat log buffer and batches) for expired live sessions.';

    public function handle(): int
    {
        return $this->trackTime(function () {
            $expiredBefore = now();

            // One join delete per table: rows of sessions cleaned up on an earlier run are already gone, so each
            // statement only walks the rows still left in that table instead of every session that ever expired.
            foreach (LiveSession::CHILD_MODEL_CLASSES as $childModelClass) {
                $childTable = (new $childModelClass())->getTable();

                $deletedCount = $childModelClass::query()
                    ->join('live_sessions', 'live_sessions.id', sprintf('%s.live_session_id', $childTable))
                    ->where('live_sessions.expires_at', '<=', $expiredBefore)
                    ->delete();

                $this->info(sprintf('%s: deleted %d row(s) of expired live sessions.', $childTable, $deletedCount));
            }

            return 0;
        });
    }
}
