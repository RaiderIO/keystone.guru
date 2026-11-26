<?php

namespace App\Service\LiveSession;

use App\Models\Enemy;
use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use Illuminate\Support\Collection;

interface LiveSessionCombatStateServiceInterface
{
    /**
     * Record that an enemy (identified by npc_id + mdt_id) has been killed in this session.
     * If already present, the call is a no-op - also when a crashed buffer pass is redone.
     *
     * @return bool True if the enemy was newly recorded, false if it already existed.
     */
    public function setKilledEnemy(LiveSession $liveSession, int $npcId, int $mdtId): bool;

    /**
     * Replace every enemy in the given state with exactly these enemies. Meant for the states that are a snapshot
     * (in combat, obsolete); killed enemies only ever accumulate through {@see self::setKilledEnemy()}.
     *
     * @param Collection<int, Enemy> $enemies
     */
    public function replaceEnemies(LiveSession $liveSession, LiveSessionEnemyStateType $state, Collection $enemies): void;

    /**
     * Resolve the enemies in the given state back to live Enemy IDs via the route's mapping version.
     *
     * @return Collection<int, int>
     */
    public function getEnemyIds(LiveSession $liveSession, LiveSessionEnemyStateType $state): Collection;

    public function hasEnemies(LiveSession $liveSession, LiveSessionEnemyStateType $state): bool;

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
        ?int        $classId = null,
        ?int        $specializationId = null,
    ): LiveSessionPlayerPosition;
}
