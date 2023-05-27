<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatEvents\GenericData;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\CombatLogVersion;
use App\Models\Dungeon;
use App\Models\DungeonRoute;
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

        $dungeonRoute               = $this->initDungeonRoute($combatLogEvents);
        $enemiesFirstShowingsEvents = $this->findEnemiesFirstShowingEvents($dungeonRoute, $combatLogEvents);
//        $killZones                  = $this->getKillZonesByEnemiesFirstShowings($enemiesFirstShowingsEvents);


        return $dungeonRoute;
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
    public function findEnemiesFirstShowingEvents(DungeonRoute $dungeonRoute, Collection $combatLogEvents): Collection
    {
        $validNpcIds = $dungeonRoute->dungeon->npcs()->pluck('id');

        $foundEnemies = collect();
        $resultEvents = collect();

        foreach ($combatLogEvents as $combatLogEvent) {
            // We skip all non-advanced combat log events, we need positional information of NPCs.
            if (!($combatLogEvent instanceof AdvancedCombatLogEvent)) {
                continue;
            }

            if ($this->hasGenericDataNewEnemy($validNpcIds, $foundEnemies, $combatLogEvent->getGenericData())) {
                $resultEvents->push($combatLogEvent);
            }
        }

        dd($resultEvents->map(function (AdvancedCombatLogEvent $event) {
            $sourceGuid = $event->getGenericData()->getSourceGuid();
            $destGuid   = $event->getGenericData()->getDestGuid();
            if ($sourceGuid instanceof Creature) {
                return sprintf(
                    'source: %s -> %s @ %s,%s',
                    $sourceGuid->getGuid(),
                    $event->getGenericData()->getSourceName(),
                    $event->getAdvancedData()->getPositionX(),
                    $event->getAdvancedData()->getPositionY(),
                );
            } else if ($destGuid instanceof Creature) {
                return sprintf(
                    'dest: %s -> %s @ %s,%s',
                    $destGuid->getGuid(),
                    $event->getGenericData()->getDestName(),
                    $event->getAdvancedData()->getPositionX(),
                    $event->getAdvancedData()->getPositionY(),
                );
            }
        }));

        return $resultEvents;
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
}
