<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use Illuminate\Support\Collection;

interface LiveSessionCombatStateServiceInterface
{
    /**
     * Record that an enemy (identified by npc_id + mdt_id) has been killed in this session.
     * If already present, the call is a no-op.
     *
     * @return bool True if the enemy was newly recorded, false if it already existed.
     */
    public function setKilledEnemy(LiveSession $liveSession, int $npcId, int $mdtId): bool;

    /**
     * Replace the full set of persisted obsolete enemies for this session.
     *
     * @param array<int, array{npc_id: int, mdt_id: int}> $npcMdtPairs
     */
    public function replaceObsoleteEnemies(LiveSession $liveSession, array $npcMdtPairs): void;

    /**
     * Resolve the obsolete-enemy rows back to live Enemy IDs via the route's mapping version.
     *
     * @return Collection<int, int>
     */
    public function getObsoleteEnemyIds(LiveSession $liveSession): Collection;

    /**
     * Upsert the latest known position for a player/character within this session.
     * Returns the persisted model with the liveSession relation pre-loaded.
     */
    public function setPlayerPosition(
        LiveSession $liveSession,
        string      $playerGuid,
        string      $characterName,
        float       $lat,
        float       $lng,
        int         $floorId,
    ): LiveSessionPlayerPosition;
}
