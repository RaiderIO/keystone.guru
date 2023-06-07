<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\EnemyPosition;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\DungeonRouteAffixGroup;
use App\Models\Faction;
use App\Models\Floor;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Service\CombatLog\Exceptions\AdvancedLogNotEnabledException;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Exceptions\NoChallangeModeStartFoundException;
use App\Service\CombatLog\Logging\CombatLogDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\Models\CombatLogDungeonRouteFilter;
use App\Service\CombatLog\Models\DungeonRouteBuilder;
use App\Service\CombatLog\Models\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\Models\ResultEvents\ChallengeModeEnd as ChallengeModeEndResultEvent;
use App\Service\CombatLog\Models\ResultEvents\ChallengeModeStart as ChallengeModeStartResultEvent;
use App\Service\CombatLog\Models\ResultEvents\EnemyEngaged;
use App\Service\CombatLog\Models\ResultEvents\EnemyKilled;
use App\Service\CombatLog\Models\ResultEvents\MapChange as MapChangeResultEvent;
use App\Service\Season\SeasonServiceInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CombatLogDungeonRouteService implements CombatLogDungeonRouteServiceInterface
{
    private CombatLogService $combatLogService;

    private SeasonServiceInterface $seasonService;

    private CombatLogDungeonRouteServiceLoggingInterface $log;

    /**
     * @param CombatLogService                             $combatLogService
     * @param SeasonServiceInterface                       $seasonService
     * @param CombatLogDungeonRouteServiceLoggingInterface $log
     */
    public function __construct(
        CombatLogService $combatLogService,
        SeasonServiceInterface $seasonService,
        CombatLogDungeonRouteServiceLoggingInterface $log
    ) {
        $this->combatLogService = $combatLogService;
        $this->seasonService    = $seasonService;
        $this->log              = $log;
    }
    /**
     * @param string $combatLogFilePath
     *
     * @return Collection
     * @throws AdvancedLogNotEnabledException
     * @throws DungeonNotSupportedException
     * @throws NoChallangeModeStartFoundException
     */
    public function getResultEvents(string $combatLogFilePath): Collection
    {
        ini_set('memory_limit', '2G');
        $combatLogEvents = $this->combatLogService->parseCombatLogToEvents($combatLogFilePath);

        $dungeonRoute = $this->initDungeonRoute($combatLogEvents);

        return (new CombatLogDungeonRouteFilter($dungeonRoute, $combatLogEvents))->filter();
    }

    /**
     * @param string $combatLogFilePath
     *
     * @return DungeonRoute
     *
     * @throws InvalidArgumentException If combat log does not exist
     * @throws AdvancedLogNotEnabledException
     * @throws DungeonNotSupportedException
     * @throws NoChallangeModeStartFoundException
     * @throws Exception
     */
    public function convertCombatLogToDungeonRoute(string $combatLogFilePath): DungeonRoute
    {
        ini_set('memory_limit', '2G');
        $combatLogEvents = $this->combatLogService->parseCombatLogToEvents($combatLogFilePath);

        $dungeonRoute = $this->initDungeonRoute($combatLogEvents);

        $resultEvents = (new CombatLogDungeonRouteFilter($dungeonRoute, $combatLogEvents))->filter();

//        dd($resultEvents->map(function (BaseResultEvent $resultEvent)
//        {
//            if ($resultEvent instanceof EnemyEngaged) {
//                return sprintf('%s: %s -> %s',
//                    $resultEvent->getBaseEvent()->getTimestamp()->toDateTimeString(),
//                    get_class($resultEvent),
//                    $resultEvent->getGuid()->getGuid()
//                );
//            } elseif ($resultEvent instanceof EnemyKilled) {
//                return sprintf('%s: %s -> %s',
//                    $resultEvent->getBaseEvent()->getTimestamp()->toDateTimeString(),
//                    get_class($resultEvent),
//                    $resultEvent->getUnitDiedEvent()->getGenericData()->getDestGuid()->getGuid()
//                );
//            } else {
//                return get_class($resultEvent);
//            }
//        }));

        // Store found enemy positions in the database for analyzing
        $this->saveEnemyPositions($resultEvents);

        $dungeonRoute = (new DungeonRouteBuilder($dungeonRoute, $resultEvents))->build();

        if (config('app.env') !== 'production') {
            $this->generateMapIconsFromEvents($dungeonRoute->dungeon, $dungeonRoute->mappingVersion, $resultEvents, $dungeonRoute, true);
        }

        return $dungeonRoute;
    }

    //    /**
    //     * @param string $combatLogFilePath
    //     * @param string $guid
    //     * @return Collection
    //     * @throws AdvancedLogNotEnabledException
    //     * @throws DungeonNotSupportedException
    //     * @throws NoChallangeModeStartFoundException
    //     */
    //    public function convertCombatLogToEventsOfSpecificEnemy(string $combatLogFilePath, string $guid): Collection
    //    {
    //        ini_set('memory_limit', '2G');
    //        $combatLogEvents = $this->combatLogService->parseCombatLogToEvents($combatLogFilePath);
    //
    //        $dungeonRoute = $this->initDungeonRoute($combatLogEvents);
    //        return $this->findEventsForSpecificEnemy($dungeonRoute, $combatLogEvents, $guid);
    //    }

    /**
     * @param Collection|BaseEvent[] $combatLogEvents
     *
     * @return DungeonRoute
     * @throws AdvancedLogNotEnabledException
     * @throws DungeonNotSupportedException
     * @throws NoChallangeModeStartFoundException
     */
    private function initDungeonRoute(Collection $combatLogEvents): DungeonRoute
    {
        $dungeonRoute = null;

        foreach ($combatLogEvents as $combatLogEvent) {
            if ($combatLogEvent instanceof CombatLogVersion && !$combatLogEvent->isAdvancedLogEnabled()) {
                throw new AdvancedLogNotEnabledException(
                    'Advanced combat logging must be enabled in order to create a dungeon route from a combat log!'
                );
            } // If we found the start, keep track of it
            elseif ($combatLogEvent instanceof ChallengeModeStart) {
                try {
                    $dungeon = Dungeon::where('map_id', $combatLogEvent->getInstanceID())->firstOrFail();
                } catch (Exception $exception) {
                    throw new DungeonNotSupportedException(
                        sprintf('Dungeon with instance ID %d not found', $combatLogEvent->getInstanceID())
                    );
                }

                $currentMappingVersion = $dungeon->getCurrentMappingVersion();

                $dungeonRoute = DungeonRoute::create([
                    'public_key'         => DungeonRoute::generateRandomPublicKey(),
                    'author_id'          => 1,
                    'dungeon_id'         => $dungeon->id,
                    'mapping_version_id' => $currentMappingVersion->id,
                    'faction_id'         => Faction::ALL[Faction::FACTION_UNSPECIFIED],
                    'published_state_id' => PublishedState::ALL[PublishedState::WORLD_WITH_LINK],
                    'title'              => __($dungeon->name),
                    'level_min'          => $combatLogEvent->getKeystoneLevel(),
                    'level_max'          => $combatLogEvent->getKeystoneLevel(),
                    'expires_at'         => Carbon::now()->addHours(
                        config('keystoneguru.sandbox_dungeon_route_expires_hours')
                    )->toDateTimeString(),
                ]);

                $dungeonRoute->dungeon        = $dungeon;
                $dungeonRoute->mappingVersion = $currentMappingVersion;

                // Find the correct affix groups that match the affix combination the dungeon was started with
                $currentSeasonForDungeon = $dungeon->getActiveSeason($this->seasonService);
                if ($currentSeasonForDungeon !== null) {
                    $affixIds            = collect($combatLogEvent->getAffixIDs());
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

                break;
            } // Otherwise, we skip all events until we are fully initialized

        }

        if ($dungeonRoute === null) {
            throw new NoChallangeModeStartFoundException();
        }

        return $dungeonRoute;
    }

    /**
     * @param Collection|BaseResultEvent[] $resultEvents
     *
     * @return void
     */
    private function saveEnemyPositions(Collection $resultEvents): void
    {
        // Save each enemy
        $enemyPositionAttributes = [];
        $currentFloor            = null;

        $now = Carbon::now()->toDateTimeString();

        $challengeModeStart = $challengeModeEnd = null;

        foreach ($resultEvents as $resultEvent) {
            // Track the starts and ends. Don't do anything just yet with this
            if ($resultEvent instanceof ChallengeModeStartResultEvent) {
                $challengeModeStart = $resultEvent;
                continue;
            }
            if ($resultEvent instanceof ChallengeModeEndResultEvent) {
                $challengeModeEnd = $resultEvent;
                continue;
            }

            // Keep track of the current floor
            if ($resultEvent instanceof MapChangeResultEvent) {
                $currentFloor = $resultEvent->getFloor();
                continue;
            }

            // Only looking for points of engagement
            if (!($resultEvent instanceof EnemyEngaged) || $currentFloor === null) {
                continue;
            }

            $latLng = $currentFloor->calculateMapLocationForIngameLocation(
                $resultEvent->getEngagedEvent()->getAdvancedData()->getPositionX(),
                $resultEvent->getEngagedEvent()->getAdvancedData()->getPositionY(),
            );

            $enemyPositionAttributes[] = [
                'challenge_mode_run_id' => null,
                'floor_id'              => $currentFloor->id,
                'npc_id'                => $resultEvent->getGuid()->getId(),
                'guid'                  => $resultEvent->getGuid()->getGuid(),
                'lat'                   => $latLng['lat'],
                'lng'                   => $latLng['lng'],
                'created_at'            => $now,
            ];
        }

        // Insert a run
        /** @var ChallengeModeRun $challengeModeRun */
        $challengeModeRun = ChallengeModeRun::create([
            'dungeon_id'    => $challengeModeStart->getDungeon()->id,
            'level'         => $challengeModeStart->getChallengeModeStartEvent()->getKeystoneLevel(),
            'success'       => $challengeModeEnd->getChallengeModeEndEvent()->getSuccess(),
            'total_time_ms' => $challengeModeEnd->getChallengeModeEndEvent()->getTotalTimeMS(),
            'created_at'    => $now,
        ]);

        // Couple the run to the attributes we generated before
        foreach ($enemyPositionAttributes as &$enemyPositionAttribute) {
            $enemyPositionAttribute['challenge_mode_run_id'] = $challengeModeRun->id;
        }

        // If we didn't insert anything there were issues with GUIDs colliding
        if (EnemyPosition::insertOrIgnore($enemyPositionAttributes) === 0) {
            // Then we don't want duplicates - get rid of the challenge mode run
            $challengeModeRun->delete();
        }
    }

    //    public function findEventsForSpecificEnemy(DungeonRoute $dungeonRoute, Collection $combatLogEvents, string $guid): Collection
    //    {
    //        $resultEvents = collect();
    //
    //        foreach ($combatLogEvents as $combatLogEvent) {
    //            if ($combatLogEvent instanceof MapChange) {
    //                $resultEvents->push($combatLogEvent);
    //                continue;
    //            }
    //
    //            if (!$this->isEnemyCombatLogEntry($combatLogEvent)) {
    //                continue;
    //            }
    //
    //            if (optional($combatLogEvent->getGenericData()->getSourceGuid())->getGuid() === $guid ||
    //                optional($combatLogEvent->getGenericData()->getDestGuid())->getGuid() === $guid) {
    //                $resultEvents->push($combatLogEvent);
    //            }
    //        }
    //
    //        return $resultEvents;
    //    }

    /**
     * @param Dungeon                      $dungeon
     * @param MappingVersion               $mappingVersion
     * @param Collection|BaseResultEvent[] $resultEvents
     * @param DungeonRoute|null            $dungeonRoute
     * @param bool                         $save
     *
     * @return Collection
     */
    public function generateMapIconsFromEvents(
        Dungeon $dungeon,
        MappingVersion $mappingVersion,
        Collection $resultEvents,
        ?DungeonRoute $dungeonRoute = null,
        bool $save = false
    ): Collection {
        $result = collect();

        $id           = 10000000;
        $currentFloor = null;
        foreach ($resultEvents as $resultEvent) {
            if ($resultEvent instanceof MapChangeResultEvent) {
                try {
                    $currentFloor = Floor
                        ::where('ui_map_id', $resultEvent->getMapChangeEvent()->getUiMapID())
                        ->firstOrFail();
                } catch (Exception $exception) {
                    dd($resultEvent->getMapChangeEvent()->getUiMapID());
                }
                continue;
            } elseif ($currentFloor === null) {
                continue;
            } elseif ($resultEvent instanceof ChallengeModeEndResultEvent) {
                break;
            } elseif (!($resultEvent->getBaseEvent() instanceof AdvancedCombatLogEvent)) {
                // Non-advanced combat logs don't have the info we need
                continue;
            }

            /** @var AdvancedCombatLogEvent $combatLogEvent */
            $combatLogEvent = $resultEvent->getBaseEvent();

            $latLng = $currentFloor->calculateMapLocationForIngameLocation(
                $combatLogEvent->getAdvancedData()->getPositionX(),
                $combatLogEvent->getAdvancedData()->getPositionY(),
            );

            $comment    = '';
            $sourceGuid = $combatLogEvent->getGenericData()->getSourceGuid();
            $destGuid   = $combatLogEvent->getGenericData()->getDestGuid();
            if ($sourceGuid instanceof Creature && $sourceGuid->getUnitType() !== Creature::CREATURE_UNIT_TYPE_PET) {
                $comment = sprintf(
                    '%s: source: %s -> %s @ %s,%s',
                    $combatLogEvent->getTimestamp()->toDateTimeString('millisecond'),
                    $sourceGuid->getGuid(),
                    $combatLogEvent->getGenericData()->getSourceName(),
                    $combatLogEvent->getAdvancedData()->getPositionX(),
                    $combatLogEvent->getAdvancedData()->getPositionY(),
                );
            } elseif ($destGuid instanceof Creature) {
                $comment = sprintf(
                    '%s: dest: %s -> %s @ %s,%s',
                    $combatLogEvent->getTimestamp()->toDateTimeString('millisecond'),
                    $destGuid->getGuid(),
                    $combatLogEvent->getGenericData()->getDestName(),
                    $combatLogEvent->getAdvancedData()->getPositionX(),
                    $combatLogEvent->getAdvancedData()->getPositionY(),
                );
            }

            $mapIcon = new MapIcon([
                'mapping_version_id' => $mappingVersion->id,
                'floor_id'           => $currentFloor->id,
                'dungeon_route_id'   => optional($dungeonRoute)->id ?? null,
                'team_id'            => null,
                'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DOT_YELLOW],
                'lat'                => $latLng['lat'],
                'lng'                => $latLng['lng'],
                'comment'            => $comment,
                'permanent_tooltip'  => 0,
            ]);

            $mapIcon->seasonal_index = null;
            if ($save) {
                $mapIcon->save();
            } else {
                $mapIcon->id = $id;

                $id++;
            }

            $result->push($mapIcon);
        }

        return $result;
    }
}
