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
use App\Service\CombatLog\Logging\CreateRouteBodyDungeonRouteBuilderLoggingInterface;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteNpc;
use App\Service\Season\SeasonServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * @property Collection|CreateRouteNpc[] $enemiesKilledInCurrentPull
 * @property Collection|CreateRouteNpc[] $currentEnemiesInCombat
 *
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

        $this->currentEnemiesInCombat     = collect();
        $this->enemiesKilledInCurrentPull = collect();


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
     */
    private function initDungeonRoute(): DungeonRoute
    {
        $dungeon               = Dungeon::where('map_id', $this->createRouteBody->challengeMode->mapId)->firstOrFail();
        $currentMappingVersion = $dungeon->getCurrentMappingVersion();

        $dungeonRoute = DungeonRoute::create([
            'public_key'         => DungeonRoute::generateRandomPublicKey(),
            'author_id'          => 1,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $currentMappingVersion->id,
            'faction_id'         => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD_WITH_LINK],
            'title'              => __($dungeon->name),
            'level_min'          => $this->createRouteBody->challengeMode->level,
            'level_max'          => $this->createRouteBody->challengeMode->level,
            'expires_at'         => Carbon::now()->addHours(
                config('keystoneguru.sandbox_dungeon_route_expires_hours')
            )->toDateTimeString(),
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
        $npcEngagedEvents = $this->createRouteBody->npcs->map(function (CreateRouteNpc $npc) {
            return [
                'type'      => 'engaged',
                'timestamp' => $npc->getEngagedAt(),
                'npc'       => $npc,
            ];
        });

        $npcDiedEvents = $this->createRouteBody->npcs->map(function (CreateRouteNpc $npc) {
            return [
                'type'      => 'died',
                'timestamp' => $npc->getDiedAt(),
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
//            $timestamp = $event['timestamp'];
//            $event['date'] = $timestamp->toDateTimeString();
//            $event['guid'] = $event['npc']->spawnUid;
//
//            unset($event['npc']);
//            unset($event['timestamp']);
//
//            return $event;
//        }));

        foreach ($npcEngagedAndDiedEvents as $event) {
            /** @var $event array{type: string, timestamp: Carbon, npc: CreateRouteNpc} */
            $realUiMapId = Floor::UI_MAP_ID_MAPPING[$event['npc']->coord->uiMapId] ?? $event['npc']->coord->uiMapId;
            if ($this->currentFloor === null || $realUiMapId !== $this->currentFloor->ui_map_id) {
                $this->currentFloor = Floor::findByUiMapId($event['npc']->coord->uiMapId);
            }

            $uniqueUid = $event['npc']->getUniqueUid();
            if ($event['type'] === 'engaged') {
                $this->currentEnemiesInCombat->put($uniqueUid, $event['npc']);
            } else if ($event['type'] === 'died') {
                $this->currentEnemiesInCombat->forget($uniqueUid);
                $this->enemiesKilledInCurrentPull->put($uniqueUid, $event['npc']);
            }

            if ($this->currentEnemiesInCombat->isEmpty()) {
//                dd($this->enemiesKilledInCurrentPull);
                $this->createPull();
            }
        }

        // Ensure that we create a final pull if need be
        if ($this->enemiesKilledInCurrentPull->isNotEmpty()) {
            $this->log->buildKillZonesCreateNewFinalPull($this->enemiesKilledInCurrentPull->keys()->toArray());
            $this->createPull();
        }

        $this->recalculateEnemyForcesOnDungeonRoute();
    }

    /**
     * @return Collection|array{array{npcId: int, x: float, y: float}}
     */
    public function convertEnemiesKilledInCurrentPull(): Collection
    {
        return $this->enemiesKilledInCurrentPull->map(function (CreateRouteNpc $npc) {
            return [
                'npcId' => $npc->npcId,
                'x'     => $npc->coord->x,
                'y'     => $npc->coord->y,
            ];
        });
    }
}