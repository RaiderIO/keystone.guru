<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession\LiveSession;
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
     * Resolve the killed-enemy rows back to live Enemy IDs via the route's mapping version.
     *
     * @return Collection<int, int>
     */
    public function getKilledEnemyIds(LiveSession $liveSession): Collection;

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
     */
    public function setPlayerPosition(
        LiveSession $liveSession,
        string      $playerGuid,
        string      $characterName,
        float       $lat,
        float       $lng,
        int         $floorId,
    ): void;

    /**
     * Return all known player positions for this session.
     *
     * @return Collection<int, array{player_guid: string, character_name: string, lat: float, lng: float, floor_id: int}>
     */
    public function getPlayerPositions(LiveSession $liveSession): Collection;
}
