<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\GenericData;
use App\Logic\CombatLog\CombatEvents\Prefixes\SpellBuilding;
use App\Logic\CombatLog\CombatEvents\Prefixes\SpellPeriodic;
use App\Logic\CombatLog\CombatEvents\Suffixes\Damage;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\Guid\Player;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeEnd;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use App\Logic\CombatLog\SpecialEvents\UnitDied;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
use App\Models\Floor;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc;
use App\Models\NpcClassification;
use App\Models\Spell;
use App\Service\CombatLog\Exceptions\AdvancedLogNotEnabledException;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Exceptions\NoChallangeModeStartFoundException;
use App\Service\CombatLog\Logging\CombatLogDungeonRouteServiceLoggingInterface;
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
     */
    public function convertCombatLogToDungeonRoute(string $combatLogFilePath): DungeonRoute
    {
        $combatLogEvents = $this->combatLogService->parseCombatLogToEvents($combatLogFilePath);

        $dungeonRoute                = $this->initDungeonRoute($combatLogEvents);
        $enemiesFirstSightingsEvents = $this->findEventsOfEnemiesFirstSightingAndDeath($dungeonRoute, $combatLogEvents);
//        $killZones                  = $this->getKillZonesByEnemiesFirstShowings($enemiesFirstShowingsEvents);


        return $dungeonRoute;
    }

    /**
     * @param string $combatLogFilePath
     * @return Collection
     * @throws AdvancedLogNotEnabledException
     * @throws DungeonNotSupportedException
     * @throws NoChallangeModeStartFoundException
     */
    public function convertCombatLogToEventsOfEnemiesFirstSightingAndDeaths(string $combatLogFilePath): Collection
    {
        ini_set('memory_limit', '2G');
        $combatLogEvents = $this->combatLogService->parseCombatLogToEvents($combatLogFilePath);

        $dungeonRoute = $this->initDungeonRoute($combatLogEvents);
        return $this->findEventsOfEnemiesFirstSightingAndDeath($dungeonRoute, $combatLogEvents);
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
     * @param Collection $combatLogEvents
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

                $dungeonRoute          = new DungeonRoute([
                    'dungeon_id' => $dungeon->id,
                ]);
                $dungeonRoute->dungeon = $dungeon;

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


    /**
     *
     * @param DungeonRoute $dungeonRoute
     * @param Collection $combatLogEvents
     * @return Collection
     */
    public function findEventsOfEnemiesFirstSightingAndDeath(DungeonRoute $dungeonRoute, Collection $combatLogEvents): Collection
    {
        // @TODO Fetch current mapping version, fetch enemy forces for said mapping version and filter in the query instead
        $validNpcIds = $dungeonRoute->dungeon->npcs()->get()->filter(function (Npc $npc) {
            return $npc->classification_id === NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS] || $npc->enemyForces->enemy_forces > 0;
        })->pluck('id');

        $foundEnemies = collect();
        $resultEvents = collect();

        foreach ($combatLogEvents as $combatLogEvent) {
            // Map changes yes please
            if ($combatLogEvent instanceof MapChange) {
                $resultEvents->push($combatLogEvent);
                continue;
            }

            // We also want unit deaths of currently known enemies
            if ($combatLogEvent instanceof UnitDied && $foundEnemies->contains($combatLogEvent->getGenericData()->getDestGuid())) {
                $resultEvents->push($combatLogEvent);
                continue;
            }

            // Ignore all non-combat events going forward
            if (!$this->isEnemyCombatLogEntry($combatLogEvent)) {
                continue;
            }

            // Check if this combat event is relevant and if it has a new NPC that we're interested in
            if ($this->hasGenericDataNewEnemy($validNpcIds, $foundEnemies, $combatLogEvent->getGenericData())) {
                // If it does we want to keep this event
                $resultEvents->push($combatLogEvent);
            }
        }

//        dd($resultEvents->map(function (BaseEvent $event) {
//            if ($event instanceof MapChange) {
//                return $event->getUiMapName();
//            }
//            if ($event instanceof UnitDied) {
//                return sprintf('%s - %s', $event->getEventName(), $event->getGenericData()->getDestGuid());
//            }

//            $sourceGuid = $event->getGenericData()->getSourceGuid();
//            $destGuid   = $event->getGenericData()->getDestGuid();
//            if ($sourceGuid instanceof Creature) {
//                return sprintf(
//                    'source: %s -> %s @ %s,%s',
//                    $sourceGuid->getGuid(),
//                    $event->getGenericData()->getSourceName(),
//                    $event->getAdvancedData()->getPositionX(),
//                    $event->getAdvancedData()->getPositionY(),
//                );
//            } else if ($destGuid instanceof Creature) {
//                return sprintf(
//                    'dest: %s -> %s @ %s,%s',
//                    $destGuid->getGuid(),
//                    $event->getGenericData()->getDestName(),
//                    $event->getAdvancedData()->getPositionX(),
//                    $event->getAdvancedData()->getPositionY(),
//                );
//            }
//        }));

        return $resultEvents;
    }

    public function findEventsForSpecificEnemy(DungeonRoute $dungeonRoute, Collection $combatLogEvents, string $guid): Collection
    {
        $resultEvents = collect();

        foreach ($combatLogEvents as $combatLogEvent) {
            if ($combatLogEvent instanceof MapChange) {
                $resultEvents->push($combatLogEvent);
                continue;
            }

            if (!$this->isEnemyCombatLogEntry($combatLogEvent)) {
                continue;
            }

            if (optional($combatLogEvent->getGenericData()->getSourceGuid())->getGuid() === $guid ||
                optional($combatLogEvent->getGenericData()->getDestGuid())->getGuid() === $guid) {
                $resultEvents->push($combatLogEvent);
            }
        }

        return $resultEvents;
    }

    /**
     * @param BaseEvent $combatLogEvent
     * @return bool
     */
    private function isEnemyCombatLogEntry(BaseEvent $combatLogEvent): bool
    {
        // We skip all non-advanced combat log events, we need positional information of NPCs.
        if (!($combatLogEvent instanceof AdvancedCombatLogEvent)) {
            return false;
        }

        // Skip events that are not damage - they contain the location of the source (the player usually)
        if (!($combatLogEvent->getSuffix() instanceof Damage)) {
            return false;
        }

        // Spells return the location of the source, not the target.
        // So for non-creatures (such as players) we don't care about them since they can be 0..40 yards off the mark
        // But if the source is the creature itself we ARE interested in everything it can throw at us.
        $sourceGuid = $combatLogEvent->getGenericData()->getSourceGuid();
        if (!($sourceGuid instanceof Creature)) {
            if ($combatLogEvent->getPrefix() instanceof Spell) {
                return false;
            }
            if ($combatLogEvent->getPrefix() instanceof SpellBuilding) {
                return false;
            }
            if ($combatLogEvent->getPrefix() instanceof SpellPeriodic) {
                return false;
            }
        }


        return true;
    }


    /**
     * @param Collection $validNpcIds
     * @param Collection $foundEnemies
     * @param GenericData $genericData
     * @return bool
     */
    private function hasGenericDataNewEnemy(Collection $validNpcIds, Collection $foundEnemies, GenericData $genericData): bool
    {
        $guids = [
            $genericData->getSourceGuid(),
            $genericData->getDestGuid(),
        ];

        $result = false;
        foreach ($guids as $guid) {
            // We're not interested in events if they don't contain a creature
            if (!$guid instanceof Creature) {
                continue;
            }

            // Invalid NPC ID, ignore it since it can never be part of the route anyways
            if (!$validNpcIds->search($guid->getId())) {
                continue;
            }

            if ($foundEnemies->search($guid->getGuid()) === false) {
                $foundEnemies->push($guid->getGuid());
                $result = true;
                // Don't break - we MAY find 2 new enemies if there's perhaps a combat log event between two enemies
            }
        }

        return $result;
    }

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
