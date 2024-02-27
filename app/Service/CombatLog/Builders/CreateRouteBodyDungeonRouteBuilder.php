<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\Faction;
use App\Models\Floor\Floor;
use App\Models\PublishedState;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Logging\CreateRouteBodyDungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Models\ActivePull\ActivePull;
use App\Service\CombatLog\Models\ActivePull\ActivePullEnemy;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteNpc;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Auth;
use Carbon\Carbon;
use Exception;

/**
 * @author Wouter
 *
 * @since 24/06/2023
 */
class CreateRouteBodyDungeonRouteBuilder extends DungeonRouteBuilder
{
    private readonly CreateRouteBodyDungeonRouteBuilderLoggingInterface $log;

    public function __construct(
        private readonly SeasonServiceInterface $seasonService,
        CoordinatesServiceInterface $coordinatesService,
        private readonly CreateRouteBody $createRouteBody
    ) {
        $dungeonRoute = $this->initDungeonRoute();

        parent::__construct($coordinatesService, $dungeonRoute);

        /** @var CreateRouteBodyDungeonRouteBuilderLoggingInterface $log */
        $log = App::make(CreateRouteBodyDungeonRouteBuilderLoggingInterface::class);
        $this->log = $log;
    }

    public function build(): DungeonRoute
    {
        $this->buildKillZones();

        $this->buildFinished();

        return $this->dungeonRoute;
    }

    /**
     * @throws DungeonNotSupportedException
     */
    private function initDungeonRoute(): DungeonRoute
    {
        try {
            if ($this->createRouteBody->challengeMode->challengeModeId !== null) {
                $dungeon = Dungeon::where('challenge_mode_id', $this->createRouteBody->challengeMode->challengeModeId)->firstOrFail();
            } else {
                $dungeon = Dungeon::where('map_id', $this->createRouteBody->challengeMode->mapId)->firstOrFail();
            }
        } catch (Exception) {
            throw new DungeonNotSupportedException(
                sprintf('Dungeon with instance ID %d not found', $this->createRouteBody->challengeMode->mapId)
            );
        }

        $currentMappingVersion = $dungeon->currentMappingVersion;

        $dungeonRoute = DungeonRoute::create([
            'public_key' => DungeonRoute::generateRandomPublicKey(),
            'author_id' => Auth::id() ?? -1,
            'dungeon_id' => $dungeon->id,
            'mapping_version_id' => $currentMappingVersion->id,
            'faction_id' => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD_WITH_LINK],
            'title' => __($dungeon->name),
            'level_min' => $this->createRouteBody->challengeMode->level,
            'level_max' => $this->createRouteBody->challengeMode->level,
            'expires_at' => $this->createRouteBody->settings->temporary ? Carbon::now()->addHours(
                config('keystoneguru.sandbox_dungeon_route_expires_hours')
            )->toDateTimeString() : null,
        ]);

        $dungeonRoute->setRelation('dungeon', $dungeon);
        $dungeonRoute->setRelation('mappingVersion', $currentMappingVersion);

        // Find the correct affix groups that match the affix combination the dungeon was started with
        $currentSeasonForDungeon = $dungeon->getActiveSeason($this->seasonService);
        if ($currentSeasonForDungeon !== null) {
            $affixIds = collect($this->createRouteBody->challengeMode->affixes);
            $eligibleAffixGroups = AffixGroup::where('season_id', $currentSeasonForDungeon->id)->get();
            foreach ($eligibleAffixGroups as $eligibleAffixGroup) {
                // If the affix group's affixes are all in $affixIds
                if ($affixIds->diff($eligibleAffixGroup->affixes->pluck('affix_id'))->isEmpty()) {
                    // Couple the affix group to the newly created dungeon route
                    DungeonRouteAffixGroup::create([
                        'dungeon_route_id' => $dungeonRoute->id,
                        'affix_group_id' => $eligibleAffixGroup->id,
                    ]);
                }
            }
        }

        return $dungeonRoute;
    }

    private function buildKillZones(): void
    {
        $filteredNpcs = $this->createRouteBody->npcs->filter(fn (CreateRouteNpc $npc) => $this->validNpcIds->search($npc->npcId) !== false);

        $npcEngagedEvents = $filteredNpcs->map(static fn (CreateRouteNpc $npc) => [
            'type' => 'engaged',
            'timestamp' => $npc->getEngagedAt(),
            'npc' => $npc,
        ]);

        $npcDiedEvents = $filteredNpcs->map(static fn (CreateRouteNpc $npc) => [
            'type' => 'died',
            // A bit of a hack - but prevent one-shot enemies from having their diedAt event
            // potentially come _before_ engagedAt event due to sorting
            'timestamp' => $npc->getDiedAt()->addSecond(),
            'npc' => $npc,
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

        $firstEngagedAt = null;
        foreach ($npcEngagedAndDiedEvents as $event) {
            /** @var $event array{type: string, timestamp: Carbon, npc: CreateRouteNpc} */
            $realUiMapId = Floor::UI_MAP_ID_MAPPING[$event['npc']->coord->uiMapId] ?? $event['npc']->coord->uiMapId;
            if ($this->currentFloor === null || $realUiMapId !== $this->currentFloor->ui_map_id) {
                $this->currentFloor = Floor::findByUiMapId($event['npc']->coord->uiMapId, $this->dungeonRoute->dungeon_id);
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
                } elseif ($activePull->isCompleted()) {
                    $activePull = $this->activePullCollection->addNewPull();
                    $this->log->buildKillZonesCreateNewActivePullChainPullCompleted();
                } // Check if we need to account for chain pulling
                elseif (($activePullAverageHPPercent = $activePull->getAverageHPPercentAt($event['npc']->getEngagedAt()))
                    <= self::CHAIN_PULL_DETECTION_HP_PERCENT) {
                    $activePull = $this->activePullCollection->addNewPull();
                    $this->log->buildKillZonesCreateNewActiveChainPull($activePullAverageHPPercent, self::CHAIN_PULL_DETECTION_HP_PERCENT);
                }

                $activePullEnemy = $this->createActivePullEnemy($event['npc']);
                $resolvedEnemy = $this->findUnkilledEnemyForNpcAtIngameLocation(
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
            } elseif ($event['type'] === 'died') {
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
                $firstActivePull = $this->activePullCollection->first();
                $firstActivePullCompleted = $firstActivePull?->isCompleted() ?? false;
                foreach ($this->activePullCollection as $pullIndex => $activePull) {
                    /** @var $activePull ActivePull */
                    if ($activePull->isCompleted()) {
                        if (! $firstActivePullCompleted) {
                            // Chain pulls are NEVER completed before the original pull! If they ARE, then it wasn't a
                            // chain pull but more like a delayed pull into a big one
                            $firstActivePull->merge($activePull);
                        } else {
                            $this->determineSpellsCastBetween($activePull, $event['npc']->getDiedAt());

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

            $this->determineSpellsCastBetween($activePull);
            $this->createPull($activePull);
        }
    }

    private function determineSpellsCastBetween(ActivePull $activePull, ?Carbon $lastDiedAt = null): void
    {
        $firstEngagedAt = null;
        foreach ($activePull->getEnemiesKilled() as $killedEnemy) {
            if ($firstEngagedAt === null || $killedEnemy->getEngagedAt()->isBefore($firstEngagedAt)) {
                $firstEngagedAt = $killedEnemy->getEngagedAt();
            }
        }

        // Determine the spells that were cast during this pull
        foreach ($this->createRouteBody->spells as $spell) {
            if ($lastDiedAt !== null) {
                if ($spell->getCastAt()->between($firstEngagedAt, $lastDiedAt)) {
                    $activePull->addSpell($spell->spellId);
                }
            } elseif ($spell->getCastAt()->isAfter($firstEngagedAt)) {
                $activePull->addSpell($spell->spellId);
            }
        }
    }

    private function createActivePullEnemy(CreateRouteNpc $npc): ActivePullEnemy
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
