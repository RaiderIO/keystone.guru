<?php

namespace App\Repositories\Database\LiveSession;

use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionEnemyState;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Repositories\Database\DatabaseRepository;
use App\Repositories\Interfaces\LiveSession\LiveSessionEnemyStateRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LiveSessionEnemyStateRepository extends DatabaseRepository implements LiveSessionEnemyStateRepositoryInterface
{
    public function __construct()
    {
        parent::__construct(LiveSessionEnemyState::class);
    }

    public function addEnemy(LiveSession $liveSession, LiveSessionEnemyStateType $state, int $npcId, int $mdtId): bool
    {
        return LiveSessionEnemyState::query()->insertOrIgnore([
            'live_session_id' => $liveSession->id,
            'state'           => $state->value,
            'npc_id'          => $npcId,
            'mdt_id'          => $mdtId,
        ]) === 1;
    }

    public function removeEnemy(LiveSession $liveSession, LiveSessionEnemyStateType $state, int $npcId, int $mdtId): bool
    {
        return LiveSessionEnemyState::query()
            ->where('live_session_id', $liveSession->id)
            ->where('state', $state->value)
            ->where('npc_id', $npcId)
            ->where('mdt_id', $mdtId)
            ->delete() > 0;
    }

    public function replaceEnemies(LiveSession $liveSession, LiveSessionEnemyStateType $state, Collection $enemies): void
    {
        DB::transaction(static function () use ($liveSession, $state, $enemies) {
            LiveSessionEnemyState::query()
                ->where('live_session_id', $liveSession->id)
                ->where('state', $state->value)
                ->delete();

            if ($enemies->isEmpty()) {
                return;
            }

            LiveSessionEnemyState::query()->insert(
                $enemies->map(static fn(Enemy $enemy): array => [
                    'live_session_id' => $liveSession->id,
                    'state'           => $state->value,
                    'npc_id'          => $enemy->npc_id,
                    'mdt_id'          => $enemy->mdt_id,
                ])->values()->all(),
            );
        });
    }

    public function getEnemyIds(LiveSession $liveSession, LiveSessionEnemyStateType $state): Collection
    {
        $table = (new LiveSessionEnemyState())->getTable();

        return $liveSession->enemiesMatchingRowsOf($table)
            ->where(sprintf('%s.state', $table), $state->value)
            ->pluck('enemies.id');
    }
}
