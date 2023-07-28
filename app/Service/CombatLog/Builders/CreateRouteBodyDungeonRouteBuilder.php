<?php

namespace App\Service\CombatLog\Builders;

use App;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteAffixGroup;
use App\Models\Faction;
use App\Models\Floor;
use App\Models\PublishedState;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Logging\CreateRouteBodyDungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Models\ActivePull\ActivePull;
use App\Service\CombatLog\Models\ActivePull\CreateRouteBodyActivePull;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteNpc;
use App\Service\Season\SeasonServiceInterface;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

/**
 * @package App\Service\CombatLog\Builders
 * @author Wouter
 * @since 24/06/2023
 */
class CreateRouteBodyDungeonRouteBuilder extends DungeonRouteBuilder
{
    private SeasonServiceInterface $seasonService;

    private CreateRouteBody $createRouteBody;

    private CreateRouteBodyDungeonRouteBuilderLoggingInterface $log;

    public function __construct(
        SeasonServiceInterface $seasonService,
        CreateRouteBody        $createRouteBody
    )
    {
        $this->seasonService   = $seasonService;
        $this->createRouteBody = $createRouteBody;

        $dungeonRoute = $this->initDungeonRoute();

        parent::__construct($dungeonRoute);


        /** @var CreateRouteBodyDungeonRouteBuilderLoggingInterface $log */
        $log       = App::make(CreateRouteBodyDungeonRouteBuilderLoggingInterface::class);
        $this->log = $log;
    }

    /**
     * @return DungeonRoute
     */
    public function build(): DungeonRoute
    {
        $this->buildKillZones();

        return $this->dungeonRoute;
    }

    /**
     * @return DungeonRoute
     * @throws DungeonNotSupportedException
     */
    private function initDungeonRoute(): DungeonRoute
    {
        try {
            $dungeon = Dungeon::where('map_id', $this->createRouteBody->challengeMode->mapId)->firstOrFail();
        } catch (Exception $exception) {
            throw new DungeonNotSupportedException(
                sprintf('Dungeon with instance ID %d not found', $this->createRouteBody->challengeMode->mapId)
            );
        }
        $currentMappingVersion = $dungeon->getCurrentMappingVersion();

        $dungeonRoute = DungeonRoute::create([
            'public_key'         => DungeonRoute::generateRandomPublicKey(),
            'author_id'          => Auth::id() ?? -1,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $currentMappingVersion->id,
            'faction_id'         => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD_WITH_LINK],
            'title'              => __($dungeon->name),
            'level_min'          => $this->createRouteBody->challengeMode->level,
            'level_max'          => $this->createRouteBody->challengeMode->level,
            'expires_at'         => $this->createRouteBody->settings->temporary ? Carbon::now()->addHours(
                config('keystoneguru.sandbox_dungeon_route_expires_hours')
            )->toDateTimeString() : null,
        ]);

        $dungeonRoute->setRelation('dungeon', $dungeon);
        $dungeonRoute->setRelation('mappingVersion', $currentMappingVersion);

        // Find the correct affix groups that match the affix combination the dungeon was started with
        $currentSeasonForDungeon = $dungeon->getActiveSeason($this->seasonService);
        if ($currentSeasonForDungeon !== null) {
            $affixIds            = collect($this->createRouteBody->challengeMode->affixes);
            $eligibleAffixGroups = AffixGroup::where('season_id', $currentSeasonForDungeon->id)->get();
            foreach ($eligibleAffixGroups as $eligibleAffixGroup) {
                // If the affix group's affixes are all in $affixIds
                if ($affixIds->diff($eligibleAffixGroup->affixes->pluck('affix_id'))->isEmpty()) {
                    // Couple the affix group to the newly created dungeon route
                    DungeonRouteAffixGroup::create([
                        'dungeon_route_id' => $dungeonRoute->id,
                        'affix_group_id'   => $eligibleAffixGroup->id,
                    ]);
                }
            }
        }

        return $dungeonRoute;
    }

    /**
     * @return void
     */
    private function buildKillZones(): void
    {
        $filteredNpcs = $this->createRouteBody->npcs->filter(function (CreateRouteNpc $npc) {
            return $this->validNpcIds->search($npc->npcId) !== false;
        });

        $npcEngagedEvents = $filteredNpcs->map(function (CreateRouteNpc $npc) {
            return [
                'type'      => 'engaged',
                'timestamp' => $npc->getEngagedAt(),
                'npc'       => $npc,
            ];
        });

        $npcDiedEvents = $filteredNpcs->map(function (CreateRouteNpc $npc) {
            return [
                'type'      => 'died',
                // A bit of a hack - but prevent one-shot enemies from having their diedAt event
                // potentially come _before_ engagedAt event due to sorting
                'timestamp' => $npc->getDiedAt()->addSecond(),
                'npc'       => $npc,
            ];
        });

        $npcEngagedAndDiedEvents = $npcEngagedEvents
            ->merge($npcDiedEvents)
            ->sortBy(function (array $event) {
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
                $this->currentFloor = Floor::findByUiMapId($event['npc']->coord->uiMapId);
            }

            $uniqueUid = $event['npc']->getUniqueUid();
            if ($event['type'] === 'engaged') {
                if ($firstEngagedAt === null) {
                    $firstEngagedAt = $event['npc']->getEngagedAt();
                }

                if ($this->activePulls->isEmpty()) {
                    $activePull = new CreateRouteBodyActivePull();
                    $this->activePulls->push($activePull);

                    $this->log->buildKillZonesCreateNewActivePull();
                } else {
                    /** @var ActivePull $activePull */
                    $activePull = $this->activePulls->last();
                }

                // Check if we need to account for chain pulling
                $activePullAverageHPPercent = $activePull->getAverageHPPercentAt($event['npc']->getEngagedAt());
                if ($activePullAverageHPPercent <= self::CHAIN_PULL_DETECTION_HP_PERCENT) {
                    $activePull = new CreateRouteBodyActivePull();
                    $this->activePulls->push($activePull);

                    $this->log->buildKillZonesCreateNewActiveChainPull($activePullAverageHPPercent, self::CHAIN_PULL_DETECTION_HP_PERCENT);
                }

                $activePull->enemyEngaged($uniqueUid, $event['npc']);
                $this->log->buildKillZonesEnemyEngaged($uniqueUid, $event['npc']->getEngagedAt()->toDateTimeString());
            } else if ($event['type'] === 'died') {
                // Find the pull that this enemy is part of
                foreach ($this->activePulls as $activePull) {
                    if ($activePull->isEnemyInCombat($uniqueUid)) {
                        $activePull->enemyKilled($uniqueUid, $event['npc']);
                        $this->log->buildKillZonesEnemyKilled($uniqueUid, $event['npc']->getDiedAt()->toDateTimeString());
                    }
                }
            }

            // Handle spells and the actual creation of pulls
            foreach ($this->activePulls as $pullIndex => $activePull) {
                if ($activePull->getEnemiesInCombat()->isEmpty()) {
                    $this->determineSpellsCastBetween($activePull, $firstEngagedAt, $event['npc']->getDiedAt());

                    $this->createPull($activePull);

                    $this->activePulls->forget($pullIndex);
                }
            }
        }

        // Handle spells and the actual creation of pulls for all remaining active pulls
        foreach ($this->activePulls as $activePull) {
            $this->log->buildKillZonesCreateNewFinalPull($activePull->getEnemiesKilled()->keys()->toArray());

            $this->determineSpellsCastBetween($activePull, $firstEngagedAt);
            $this->createPull($activePull);
        }

        $this->activePulls = collect();

        $this->recalculateEnemyForcesOnDungeonRoute();
    }

    /**
     * @return Collection|array{array{npcId: int, x: float, y: float}}
     */
    public function convertEnemiesKilledInActivePull(ActivePull $activePull): Collection
    {
        return $activePull->getEnemiesKilled()->mapWithKeys(function (CreateRouteNpc $npc, string $guid) {
            return [
                $guid => [
                    'npcId' => $npc->npcId,
                    'x'     => $npc->coord->x,
                    'y'     => $npc->coord->y,
                ]
            ];
        });
    }

    /**
     * @param ActivePull  $activePull
     * @param Carbon      $firstEngagedAt
     * @param Carbon|null $diedAt
     * @return void
     */
    private function determineSpellsCastBetween(ActivePull $activePull, Carbon $firstEngagedAt, ?Carbon $diedAt = null)
    {
        // Determine the spells that were cast during this pull
        foreach ($this->createRouteBody->spells as $spell) {
            if ($diedAt !== null) {
                if ($spell->getCastAt()->between($firstEngagedAt, $diedAt)) {
                    $activePull->addSpell($spell->spellId);
                }
            } else if ($spell->getCastAt()->isAfter($firstEngagedAt)) {
                $activePull->addSpell($spell->spellId);
            }
        }
    }
}
