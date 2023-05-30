<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
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
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CombatLogDungeonRouteService implements CombatLogDungeonRouteServiceInterface
{
    private CombatLogService $combatLogService;

    private CombatLogDungeonRouteServiceLoggingInterface $log;

    /**
     * @param CombatLogService $combatLogService
     * @param CombatLogDungeonRouteServiceLoggingInterface $log
     */
    public function __construct(CombatLogService $combatLogService, CombatLogDungeonRouteServiceLoggingInterface $log)
    {
        $this->combatLogService = $combatLogService;
        $this->log              = $log;
    }


    /**
     * @param string $combatLogFilePath
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

        $dungeonRoute                = $this->initDungeonRoute($combatLogEvents);
        $dungeonRouteCombatLogEvents = (new CombatLogDungeonRouteFilter($dungeonRoute, $combatLogEvents))->filter();

        return (new DungeonRouteBuilder($dungeonRoute, $dungeonRouteCombatLogEvents))->build();
    }

    /**
     * @param string $combatLogFilePath
     * @param string $guid
     * @return Collection
     * @throws AdvancedLogNotEnabledException
     * @throws DungeonNotSupportedException
     * @throws NoChallangeModeStartFoundException
     */
    public function convertCombatLogToEventsOfSpecificEnemy(string $combatLogFilePath, string $guid): Collection
    {
        ini_set('memory_limit', '2G');
        $combatLogEvents = $this->combatLogService->parseCombatLogToEvents($combatLogFilePath);

        $dungeonRoute = $this->initDungeonRoute($combatLogEvents);
        return $this->findEventsForSpecificEnemy($dungeonRoute, $combatLogEvents, $guid);
    }


    /**
     * @param Collection|BaseEvent[] $combatLogEvents
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
            else if ($combatLogEvent instanceof ChallengeModeStart) {
                try {
                    $dungeon = Dungeon::where('map_id', $combatLogEvent->getInstanceID())->firstOrFail();
                } catch (Exception $exception) {
                    throw new DungeonNotSupportedException(
                        sprintf('Dungeon with instance ID %d not found', $combatLogEvent->getInstanceID())
                    );
                }

                $currentMappingVersion = $dungeon->getCurrentMappingVersion();

                $dungeonRoute                 = DungeonRoute::create([
                    'public_key'         => DungeonRoute::generateRandomPublicKey(),
                    'author_id'          => 1,
                    'dungeon_id'         => $dungeon->id,
                    'mapping_version_id' => $currentMappingVersion->id,
                    'faction_id'         => Faction::ALL[Faction::FACTION_UNSPECIFIED],
                    'published_state_id' => PublishedState::ALL[PublishedState::WORLD_WITH_LINK],
                    'title'              => __($dungeon->name),
                    // @TODO Capture keystone level
                    'level_min'          => 10,
                    'level_max'          => 10,
                    'expires_at'         => Carbon::now()->addHours(
                        config('keystoneguru.sandbox_dungeon_route_expires_hours')
                    )->toDateTimeString(),
                ]);
                $dungeonRoute->dungeon        = $dungeon;
                $dungeonRoute->mappingVersion = $currentMappingVersion;

                break;
                // @TODO We also know the affixes at this point, but we need to add the affix IDs to our own affixes in the database first
                // See https://wow.tools/dbc/?dbc=keystoneaffix&build=10.0.5.47660#page=1
            } // Otherwise, we skip all events until we are fully initialized

        }

        if ($dungeonRoute === null) {
            throw new NoChallangeModeStartFoundException();
        }

        return $dungeonRoute;
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
     * @param Dungeon $dungeon
     * @param MappingVersion $mappingVersion
     * @param Collection|AdvancedCombatLogEvent[] $combatLogEvents
     * @return Collection
     */
    public function generateMapIconsFromEvents(Dungeon $dungeon, MappingVersion $mappingVersion, Collection $combatLogEvents): Collection
    {
        $result = collect();

        $id           = 10000000;
        $currentFloor = null;
        foreach ($combatLogEvents as $combatLogEvent) {
            if ($combatLogEvent instanceof MapChange) {
                try {
                    $currentFloor = Floor::where('ui_map_id', $combatLogEvent->getUiMapID())->firstOrFail();
                } catch (Exception $exception) {
                    dd($combatLogEvent->getUiMapID());
                }
                continue;
            } else if ($currentFloor === null) {
                continue;
            } else if ($combatLogEvent instanceof ChallengeModeEnd) {
                break;
            } else if (!($combatLogEvent instanceof AdvancedCombatLogEvent)) {
                // Non-advanced combat logs don't have the info we need
                continue;
            }

            /** @var AdvancedCombatLogEvent $combatLogEvent */

            $latLng = $currentFloor->calculateMapLocationForIngameLocation(
                $combatLogEvent->getAdvancedData()->getPositionX(),
                $combatLogEvent->getAdvancedData()->getPositionY(),
            );

            $comment    = '';
            $sourceGuid = $combatLogEvent->getGenericData()->getSourceGuid();
            $destGuid   = $combatLogEvent->getGenericData()->getDestGuid();
            if ($sourceGuid instanceof Creature) {
                $comment = sprintf(
                    '%s: source: %s -> %s @ %s,%s',
                    $combatLogEvent->getTimestamp()->toDateTimeString('millisecond'),
                    $sourceGuid->getGuid(),
                    $combatLogEvent->getGenericData()->getSourceName(),
                    $combatLogEvent->getAdvancedData()->getPositionX(),
                    $combatLogEvent->getAdvancedData()->getPositionY(),
                );
            } else if ($destGuid instanceof Creature) {
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
                'dungeon_route_id'   => null,
                'team_id'            => null,
                'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DOT_YELLOW],
                'lat'                => $latLng['lat'],
                'lng'                => $latLng['lng'],
                'comment'            => $comment,
                'permanent_tooltip'  => 0,
            ]);

            $mapIcon->id             = $id;
            $mapIcon->seasonal_index = null;

            $result->push($mapIcon);

            $id++;
        }

        return $result;
    }
}
