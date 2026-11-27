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
     * Record that a viewer took back the overpull of an enemy (identified by npc_id + mdt_id), so overpull detection
     * leaves it alone from now on. If already present, the call is a no-op.
     *
     * @return bool True if the dismissal was newly recorded, false if it already existed.
     */
    public function setOverpullDismissed(LiveSession $liveSession, int $npcId, int $mdtId): bool;

    /**
     * Forget a dismissal recorded by {@see self::setOverpullDismissed()}, for when a viewer marks the enemy overpulled
     * again.
     *
     * @return bool True if the enemy carried a dismissal, false if it did not.
     */
    public function clearOverpullDismissed(LiveSession $liveSession, int $npcId, int $mdtId): bool;

    /**
     * Replace every enemy in the given state with exactly these enemies. Meant for the in-combat state, which is a
     * snapshot; killed enemies only ever accumulate through {@see self::setKilledEnemy()}.
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

    /**
     * Resolve the enemies in each of the given states back to live Enemy IDs via the route's mapping version, in one
     * query. Every requested state is a key of the result, holding an empty collection when no enemy is in it.
     *
     * @param array<int, LiveSessionEnemyStateType> $states
     *
     * @return Collection<string, Collection<int, int>> Keyed by {@see LiveSessionEnemyStateType::$value}.
     */
    public function getEnemyIdsPerState(LiveSession $liveSession, array $states): Collection;

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
