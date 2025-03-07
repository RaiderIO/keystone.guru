<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteNpcRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteRequestModel;
use App\Http\Models\Request\CombatLog\Route\CombatLogRouteSpellRequestModel;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Repositories\Interfaces\AffixGroup\AffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteAffixGroupRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\EnemyRepositoryInterface;
use App\Repositories\Interfaces\Floor\FloorRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneEnemyRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneRepositoryInterface;
use App\Repositories\Interfaces\KillZone\KillZoneSpellRepositoryInterface;
use App\Repositories\Interfaces\Npc\NpcRepositoryInterface;
use App\Repositories\Interfaces\SpellRepositoryInterface;
use App\Service\CombatLog\Builders\Logging\CombatLogRouteDungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Models\ActivePull\ActivePull;
use App\Service\CombatLog\Models\ActivePull\ActivePullEnemy;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Random\RandomException;

/**
 * @author Wouter
 *
 * @since 24/06/2023
 */
class CombatLogRouteDungeonRouteBuilder extends DungeonRouteBuilder
{
    private CombatLogRouteDungeonRouteBuilderLoggingInterface $log;

    /** @var Collection<int> */
    protected Collection $validSpellIds;

    /**
     * @throws DungeonNotSupportedException
     * @throws RandomException
     */
    public function __construct(
        private readonly SeasonServiceInterface       $seasonService,
        CoordinatesServiceInterface                   $coordinatesService,
        DungeonRouteRepositoryInterface               $dungeonRouteRepository,
        DungeonRouteAffixGroupRepositoryInterface     $dungeonRouteAffixGroupRepository,
        AffixGroupRepositoryInterface                 $affixGroupRepository,
        KillZoneRepositoryInterface                   $killZoneRepository,
        KillZoneEnemyRepositoryInterface              $killZoneEnemyRepository,
        KillZoneSpellRepositoryInterface              $killZoneSpellRepository,
        EnemyRepositoryInterface                      $enemyRepository,
        NpcRepositoryInterface                        $npcRepository,
        protected readonly SpellRepositoryInterface   $spellRepository,
        protected readonly FloorRepositoryInterface   $floorRepository,
        protected readonly DungeonRepositoryInterface $dungeonRepository,
        protected readonly CombatLogRouteRequestModel $combatLogRoute,
        ?int                                          $userId = null
    ) {
        $log = App::make(CombatLogRouteDungeonRouteBuilderLoggingInterface::class);

        parent::__construct($coordinatesService,
            $dungeonRouteRepository,
            $killZoneRepository,
            $killZoneEnemyRepository,
            $killZoneSpellRepository,
            $enemyRepository,
            $npcRepository,
            $this->combatLogRoute->createDungeonRoute(
                $this->seasonService,
                $dungeonRouteRepository,
                $affixGroupRepository,
                $dungeonRouteAffixGroupRepository,
                $this->dungeonRepository,
                $userId
            ),
            $log
        );

        $this->validSpellIds = $this->spellRepository->findAllById(
            $combatLogRoute->spells->map(fn(CombatLogRouteSpellRequestModel $spell) => $spell->spellId)
        );

        /** @var CombatLogRouteDungeonRouteBuilderLoggingInterface $log */
        $this->log = $log;
    }

    public function build(): DungeonRoute
    {
        $this->buildKillZones();

        $this->buildFinished();

        return $this->dungeonRoute;
    }

    private function buildKillZones(): void
    {
        $filteredNpcs = $this->combatLogRoute->npcs->filter(fn(CombatLogRouteNpcRequestModel $npc) => $this->validNpcIds->search($npc->npcId) !== false);

        $npcEngagedEvents = $filteredNpcs->map(static fn(CombatLogRouteNpcRequestModel $npc) => [
            'type'      => 'engaged',
            'timestamp' => $npc->getEngagedAt(),
            'npc'       => $npc,
        ]);

        $npcDiedEvents = $filteredNpcs->map(static fn(CombatLogRouteNpcRequestModel $npc) => [
            'type'      => 'died',
            // A bit of a hack - but prevent one-shot enemies from having their diedAt event
            // potentially come _before_ engagedAt event due to sorting
            'timestamp' => $npc->getDiedAt()->addSecond(),
            'npc'       => $npc,
        ]);

        $npcEngagedAndDiedEvents = $npcEngagedEvents
            ->merge($npcDiedEvents)
            ->sortBy(static function (array $event) {
                /** @var Carbon $timestamp */
                $timestamp = $event['timestamp'];

                return $timestamp->unix();
            });

        //        dd($npcEngagedAndDiedEvents->map(function (array $event) {
        //            /** @var Carbon $timestamp */
        //            $timestamp     = $event['timestamp'];
        //            $event['date'] = $timestamp->toDateTimeString();
        //            $event['guid'] = $event['npc']->spawnUid;
        //
        //            unset($event['npc']);
        //            unset($event['timestamp']);
        //
        //            return $event;
        //        }));

        $floorCache          = collect();
        $firstEngagedAt      = null;
        $totalSpellsAssigned = 0;
        foreach ($npcEngagedAndDiedEvents as $event) {
            /** @var $event array{type: string, timestamp: Carbon, npc: CombatLogRouteNpcRequestModel} */
            $realUiMapId = Floor::UI_MAP_ID_MAPPING[$event['npc']->coord->uiMapId] ?? $event['npc']->coord->uiMapId;
            if ($this->currentFloor === null || $realUiMapId !== $this->currentFloor->ui_map_id) {
                $newFloor = $floorCache->get($event['npc']->coord->uiMapId);

                if ($newFloor === null) {
                    $floorCache->put(
                        $event['npc']->coord->uiMapId,
                        $this->floorRepository->findByUiMapId($event['npc']->coord->uiMapId, $this->dungeonRoute->dungeon_id)
                    );
                }

                if ($newFloor === null) {
                    // First floor ever, can't find it? Assign the default floor
                    if ($this->currentFloor === null) {
                        $this->currentFloor = $this->floorRepository->getDefaultFloorForDungeon($this->dungeonRoute->dungeon_id);

                        $this->log->buildKillZonesFloorAssigningDefaultFloor($this->currentFloor->id);
                    }
                } else {
                    $this->currentFloor = $newFloor;
                    $this->log->buildKillZonesNewCurrentFloor($this->currentFloor->id, $this->currentFloor->ui_map_id);
                }
            }

            $uniqueUid = $event['npc']->getUniqueId();
            if ($event['type'] === 'engaged') {
                if ($firstEngagedAt === null) {
                    $firstEngagedAt = $event['npc']->getEngagedAt();
                }

                /** @var ActivePull|null $activePull */
                $activePull = $this->activePullCollection->last();

                if ($activePull === null) {
                    $activePull = $this->activePullCollection->addNewPull();
                    $this->log->buildKillZonesCreateNewActivePull();
                } else if ($activePull->isCompleted()) {
                    $activePull = $this->activePullCollection->addNewPull();
                    $this->log->buildKillZonesCreateNewActivePullChainPullCompleted();
                } // Check if we need to account for chain pulling
                else if (($activePullAverageHPPercent = $activePull->getAverageHPPercentAt($event['npc']->getEngagedAt()))
                    <= self::CHAIN_PULL_DETECTION_HP_PERCENT) {
                    $activePull = $this->activePullCollection->addNewPull();
                    $this->log->buildKillZonesCreateNewActiveChainPull($activePullAverageHPPercent, self::CHAIN_PULL_DETECTION_HP_PERCENT);
                }

                $activePullEnemy = $this->createActivePullEnemy($event['npc']);
                $resolvedEnemy   = $this->findUnkilledEnemyForNpcAtIngameLocation(
                    $activePullEnemy,
                    $this->activePullCollection->getInCombatGroups()
                );

                if ($resolvedEnemy === null) {
                    $this->log->buildKillZonesUnableToFindEnemyForNpc($uniqueUid);

                    continue;
                }

                // Ensure we know about the enemy being resolved fully
                $event['npc']->setResolvedEnemy($resolvedEnemy);
                $activePullEnemy->setResolvedEnemy($resolvedEnemy);

                $this->log->buildKillZonesEnemyEngaged($uniqueUid, $event['npc']->getEngagedAt()->toDateTimeString());
                $activePull->enemyEngaged($activePullEnemy);
            } else if ($event['type'] === 'died') {
                // Find the pull that this enemy is part of
                foreach ($this->activePullCollection as $activePull) {
                    /** @var $activePull ActivePull */
                    if ($activePull->isEnemyInCombat($uniqueUid)) {
                        $activePull->enemyKilled($event['npc']->getUniqueId());
                        $this->log->buildKillZonesEnemyKilled($uniqueUid, $event['npc']->getDiedAt()->toDateTimeString());
                    }
                }

                // Handle spells and the actual creation of pulls
                /** @var $firstActivePull ActivePull|null */
                $firstActivePull          = $this->activePullCollection->first();
                $firstActivePullCompleted = $firstActivePull?->isCompleted() ?? false;
                foreach ($this->activePullCollection as $pullIndex => $activePull) {
                    /** @var $activePull ActivePull */
                    if ($activePull->isCompleted()) {
                        if (!$firstActivePullCompleted) {
                            // Chain pulls are NEVER completed before the original pull! If they ARE, then it wasn't a
                            // chain pull but more like a delayed pull into a big one
                            $firstActivePull->merge($activePull);
                        } else {
                            $totalSpellsAssigned += $this->assignSpellsCastBetweenToActivePull($activePull, $event['npc']->getDiedAt());

                            $this->createPull($activePull);
                        }

                        $this->activePullCollection->forget($pullIndex);
                    }
                }
            }
        }

        // Handle spells and the actual creation of pulls for all remaining active pulls
        foreach ($this->activePullCollection as $activePull) {
            $this->log->buildKillZonesCreateNewFinalPull($activePull->getEnemiesKilled()->keys()->toArray());

            $totalSpellsAssigned += $this->assignSpellsCastBetweenToActivePull($activePull);
            $this->createPull($activePull);
        }

        if ($totalSpellsAssigned !== $this->combatLogRoute->spells->count()) {
            $this->log->buildKillZonesNotAllSpellsAssigned($totalSpellsAssigned, $this->combatLogRoute->spells->count());
        }
    }

    /**
     * @return int Returns the amount of spells assigned to the pull.
     */
    private function assignSpellsCastBetweenToActivePull(ActivePull $activePull, ?Carbon $lastDiedAt = null): int
    {
        $assignedSpells = 0;

        $firstEngagedAt = null;
        foreach ($activePull->getEnemiesKilled() as $killedEnemy) {
            if ($firstEngagedAt === null || $killedEnemy->getEngagedAt()->isBefore($firstEngagedAt)) {
                $firstEngagedAt = $killedEnemy->getEngagedAt();
            }
        }

        // Determine the spells that were cast during this pull
        foreach ($this->combatLogRoute->spells as $spell) {
            if ($lastDiedAt !== null) {
                if ($spell->getCastAt()->between($firstEngagedAt, $lastDiedAt)) {
                    if (!$this->validSpellIds->has($spell->spellId)) {
                        $this->log->determineSpellsCastBetweenInvalidSpellIdBetween($spell->spellId);

                        continue;
                    }
                    $activePull->addSpell($spell->spellId);
                    $assignedSpells++;
                }
            } else if ($spell->getCastAt()->isAfter($firstEngagedAt)) {
                if (!$this->validSpellIds->has($spell->spellId)) {
                    $this->log->determineSpellsCastBetweenInvalidSpellIdAfter($spell->spellId);

                    continue;
                }

                $activePull->addSpell($spell->spellId);
                $assignedSpells++;
            }
        }

        return $assignedSpells;
    }

    private function createActivePullEnemy(CombatLogRouteNpcRequestModel $npc): ActivePullEnemy
    {
        return new ActivePullEnemy(
            $npc->getUniqueId(),
            $npc->npcId,
            $npc->coord->x,
            $npc->coord->y,
            $npc->getEngagedAt(),
            $npc->getDiedAt()
        );
    }
}
