<?php

namespace App\Console\Commands\Scheduler\LiveSession;

use App\Console\Commands\Scheduler\SchedulerCommand;
use App\Models\Enemies\OverpulledEnemy;
use App\Models\LiveSession\LiveSession;

class CleanupExpiredLiveSessions extends SchedulerCommand
{
    protected $signature = 'livesession:cleanup-expired';

    protected $description = 'Deletes relation data (overpulled/killed/obsolete/in-combat enemies, player positions, combat log buffer) for expired live sessions.';

    public function handle(): int
    {
        return $this->trackTime(function () {
            $count = 0;

            LiveSession::query()
                ->where('expires_at', '<=', now())
                ->each(function (LiveSession $session) use (&$count) {
                    $session->overpulledEnemies()->delete();
                    OverpulledEnemy::query()->where('live_session_id', $session->id)->delete();
                    $session->killedEnemies()->delete();
                    $session->obsoleteEnemies()->delete();
                    $session->inCombatEnemies()->delete();
                    $session->playerPositions()->delete();
                    $session->combatLogBuffer()->delete();

                    $count++;
                });

            $this->info(sprintf('%d expired session(s) cleaned up.', $count));

            return 0;
        });
    }
}
