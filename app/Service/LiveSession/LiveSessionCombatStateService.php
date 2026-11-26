<?php

namespace App\Service\LiveSession;

use App\Models\LiveSession\LiveSession;
use App\Models\LiveSession\LiveSessionEnemyStateType;
use App\Models\LiveSession\LiveSessionPlayerPosition;
use App\Repositories\Interfaces\LiveSession\LiveSessionEnemyStateRepositoryInterface;
use Illuminate\Support\Collection;

class LiveSessionCombatStateService implements LiveSessionCombatStateServiceInterface
{
    public function __construct(
        private readonly LiveSessionEnemyStateRepositoryInterface $liveSessionEnemyStateRepository,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function setKilledEnemy(LiveSession $liveSession, int $npcId, int $mdtId): bool
    {
        return $this->liveSessionEnemyStateRepository->addEnemy($liveSession, LiveSessionEnemyStateType::Killed, $npcId, $mdtId);
    }

    /**
     * {@inheritDoc}
     */
    public function setOverpullDismissed(LiveSession $liveSession, int $npcId, int $mdtId): bool
    {
        return $this->liveSessionEnemyStateRepository->addEnemy($liveSession, LiveSessionEnemyStateType::OverpullDismissed, $npcId, $mdtId);
    }

    /**
     * {@inheritDoc}
     */
    public function clearOverpullDismissed(LiveSession $liveSession, int $npcId, int $mdtId): bool
    {
        return $this->liveSessionEnemyStateRepository->removeEnemy($liveSession, LiveSessionEnemyStateType::OverpullDismissed, $npcId, $mdtId);
    }

    /**
     * {@inheritDoc}
     */
    public function replaceEnemies(LiveSession $liveSession, LiveSessionEnemyStateType $state, Collection $enemies): void
    {
        $this->liveSessionEnemyStateRepository->replaceEnemies($liveSession, $state, $enemies);
    }

    /**
     * {@inheritDoc}
     */
    public function getEnemyIds(LiveSession $liveSession, LiveSessionEnemyStateType $state): Collection
    {
        return $this->liveSessionEnemyStateRepository->getEnemyIds($liveSession, $state);
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
        ?int        $classId = null,
        ?int        $specializationId = null,
    ): LiveSessionPlayerPosition {
        $model = LiveSessionPlayerPosition::query()->updateOrCreate(
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
                // Only overwrite the class/spec when we actually resolved them from a COMBATANT_INFO, so
                // a later position update without one never wipes a specialization resolved earlier. The
                // explicit null check (not array_filter()'s falsy-stripping default) matters here too -
                // class/spec ids of 0 must not be treated the same as "not resolved".
                ...array_filter([
                    'class_id'          => $classId,
                    'specialization_id' => $specializationId,
                ], static fn(?int $value): bool => $value !== null),
            ],
        );

        $model->setRelation('liveSession', $liveSession);

        return $model;
    }
}
