<?php

namespace App\Service\LiveSession;

use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionKilledEnemy;
use App\Models\LiveSession\LiveSessionObsoleteEnemy;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LiveSessionCombatStateService implements LiveSessionCombatStateServiceInterface
{
    /**
     * {@inheritDoc}
     */
    public function setKilledEnemy(LiveSession $liveSession, int $npcId, int $mdtId): bool
    {
        $model = LiveSessionKilledEnemy::query()->firstOrCreate([
            'live_session_id' => $liveSession->id,
            'npc_id'          => $npcId,
            'mdt_id'          => $mdtId,
        ]);

        return $model->wasRecentlyCreated;
    }

    /**
     * {@inheritDoc}
     */
    public function getKilledEnemyIds(LiveSession $liveSession): Collection
    {
        return $this->resolveEnemyIds('live_session_killed_enemies', $liveSession);
    }

    /**
     * {@inheritDoc}
     */
    public function replaceObsoleteEnemies(LiveSession $liveSession, array $npcMdtPairs): void
    {
        DB::transaction(static function () use ($liveSession, $npcMdtPairs) {
            LiveSessionObsoleteEnemy::query()
                ->where('live_session_id', $liveSession->id)
                ->delete();

            foreach ($npcMdtPairs as $pair) {
                LiveSessionObsoleteEnemy::query()->create([
                    'live_session_id' => $liveSession->id,
                    'npc_id'          => $pair['npc_id'],
                    'mdt_id'          => $pair['mdt_id'],
                ]);
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getObsoleteEnemyIds(LiveSession $liveSession): Collection
    {
        return $this->resolveEnemyIds('live_session_obsolete_enemies', $liveSession);
    }

    /**
     * {@inheritDoc}
     */
    public function setPlayerPosition(
        LiveSession $liveSession,
        string      $playerGuid,
        string      $characterName,
        float       $lat,
        float       $lng,
        int         $floorId,
    ): void {
        LiveSessionPlayerPosition::query()->updateOrCreate(
            [
                'live_session_id' => $liveSession->id,
                'player_guid'     => $playerGuid,
            ],
            [
                'character_name' => $characterName,
                'lat'            => $lat,
                'lng'            => $lng,
                'floor_id'       => $floorId,
                'updated_at'     => now(),
            ],
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getPlayerPositions(LiveSession $liveSession): Collection
    {
        return LiveSessionPlayerPosition::query()
            ->where('live_session_id', $liveSession->id)
            ->get();
    }

    /**
     * Resolve rows from a killed/obsolete table back to live Enemy IDs via the route's mapping version.
     *
     * @return Collection<int, int>
     */
    private function resolveEnemyIds(string $stateTable, LiveSession $liveSession): Collection
    {
        return Enemy::select('enemies.id')
            ->join($stateTable, static function (JoinClause $clause) use ($stateTable) {
                $clause->on(sprintf('%s.npc_id', $stateTable), 'enemies.npc_id')
                    ->on(sprintf('%s.mdt_id', $stateTable), 'enemies.mdt_id');
            })
            ->join('live_sessions', 'live_sessions.id', sprintf('%s.live_session_id', $stateTable))
            ->join('dungeon_routes', 'dungeon_routes.id', 'live_sessions.dungeon_route_id')
            ->whereColumn('enemies.mapping_version_id', 'dungeon_routes.mapping_version_id')
            ->where(sprintf('%s.live_session_id', $stateTable), $liveSession->id)
            ->pluck('enemies.id');
    }
}
