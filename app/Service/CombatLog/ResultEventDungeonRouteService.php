<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\Guid\Creature;
use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\EnemyPosition;
use App\Models\DungeonRoute;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\Builders\ResultEventDungeonRouteBuilder;
use App\Service\CombatLog\Exceptions\AdvancedLogNotEnabledException;
use App\Service\CombatLog\Exceptions\DungeonNotSupportedException;
use App\Service\CombatLog\Exceptions\NoChallangeModeStartFoundException;
use App\Service\CombatLog\Logging\CombatLogDungeonRouteServiceLoggingInterface;
use App\Service\CombatLog\ResultEvents\BaseResultEvent;
use App\Service\CombatLog\ResultEvents\ChallengeModeEnd as ChallengeModeEndResultEvent;
use App\Service\CombatLog\ResultEvents\ChallengeModeStart as ChallengeModeStartResultEvent;
use App\Service\CombatLog\ResultEvents\EnemyEngaged as EnemyEngagedResultEvent;
use App\Service\CombatLog\ResultEvents\MapChange as MapChangeResultEvent;
use App\Service\Season\SeasonServiceInterface;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ResultEventDungeonRouteService implements ResultEventDungeonRouteServiceInterface
{
    protected CombatLogService                           $combatLogService;
    
    protected SeasonServiceInterface                     $seasonService;
    
    private CombatLogDungeonRouteServiceLoggingInterface $log;

    /**
     * @param CombatLogService                             $combatLogService
     * @param SeasonServiceInterface                       $seasonService
     * @param CombatLogDungeonRouteServiceLoggingInterface $log
     */
    public function __construct(
        CombatLogService                             $combatLogService,
        SeasonServiceInterface                       $seasonService,
        CombatLogDungeonRouteServiceLoggingInterface $log
    )
    {
        $this->combatLogService = $combatLogService;
        $this->seasonService    = $seasonService;
        $this->log              = $log;
    }

    /**
     * @param string $combatLogFilePath
     *
     * @return Collection|DungeonRoute[]
     *
     * @throws InvalidArgumentException If combat log does not exist
     * @throws AdvancedLogNotEnabledException
     * @throws DungeonNotSupportedException
     * @throws NoChallangeModeStartFoundException
     * @throws Exception
     */
    public function convertCombatLogToDungeonRoutes(string $combatLogFilePath): Collection
    {
        ini_set('max_execution_time', 1800);

        try {
            $this->log->convertCombatLogToDungeonRoutesStart($combatLogFilePath);

            $result = collect();

            $dungeonRoute = null;
            $resultEvents = $this->combatLogService->getResultEvents($combatLogFilePath, $dungeonRoute);
            if (!($dungeonRoute instanceof DungeonRoute)) {
                throw new Exception('Unable to generate dungeon route from combat log!');
            }

            // <editor-fold desc="Debug" defaultstate="collapsed">
//            dd($resultEvents->map(function (BaseResultEvent $resultEvent) {
//                if ($resultEvent instanceof MapChangeResultEvent) {
//                    return sprintf('%s: %s -> %s',
//                        $resultEvent->getBaseEvent()->getTimestamp()->toDateTimeString(),
//                        get_class($resultEvent),
//                        __(optional($resultEvent->getFloor())->name ??
//                        sprintf('unknown floor (%s, %d)', $resultEvent->getMapChangeEvent()->getUiMapName(), $resultEvent->getMapChangeEvent()->getUiMapID()))
//                    );
//                } else if ($resultEvent instanceof EnemyEngaged) {
//                    return sprintf('%s: %s -> %s',
//                        $resultEvent->getBaseEvent()->getTimestamp()->toDateTimeString(),
//                        get_class($resultEvent),
//                        $resultEvent->getGuid()->getGuid()
//                    );
//                } else if ($resultEvent instanceof EnemyKilled) {
//                    $baseEvent = $resultEvent->getBaseEvent();
//                    if ($baseEvent instanceof GenericSpecialEvent || $baseEvent instanceof CombatLogEvent) {
//                        $genericData = $baseEvent->getGenericData();
//                    } else {
//                        return 'EVENT HAS NO GENERIC DATA';
//                    }
//
//                    return sprintf('%s: %s -> %s',
//                        $resultEvent->getBaseEvent()->getTimestamp()->toDateTimeString(),
//                        get_class($resultEvent),
//                        $genericData->getDestGuid()->getGuid()
//                    );
//                } else {
//                    return get_class($resultEvent);
//                }
//            }));
            // </editor-fold>

            // Store found enemy positions in the database for analyzing
            $this->saveEnemyPositionFromResultEvents($resultEvents);

            $dungeonRoute = (new ResultEventDungeonRouteBuilder($dungeonRoute, $resultEvents))->build();

            if (config('app.debug')) {
                $this->generateMapIconsFromEvents(
                    $dungeonRoute->mappingVersion,
                    $resultEvents,
                    $dungeonRoute
                );
            }

            $result->push($dungeonRoute);
        } finally {
            $this->log->convertCombatLogToDungeonRoutesEnd();
        }

        return $result;
    }

    /**
     * @param Collection|BaseResultEvent[] $resultEvents
     *
     * @return void
     */
    private function saveEnemyPositionFromResultEvents(Collection $resultEvents): void
    {
        try {
            $this->log->saveEnemyPositionFromResultEventsStart();
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
                if (!($resultEvent instanceof EnemyEngagedResultEvent) || $currentFloor === null) {
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
        } finally {
            $this->log->saveEnemyPositionFromResultEventsEnd();
        }
    }

    /**
     * @param MappingVersion               $mappingVersion
     * @param Collection|BaseResultEvent[] $resultEvents
     * @param DungeonRoute|null            $dungeonRoute
     *
     * @return void
     */
    private function generateMapIconsFromEvents(
        MappingVersion $mappingVersion,
        Collection     $resultEvents,
        ?DungeonRoute  $dungeonRoute = null
    ): void
    {
        $currentFloor      = null;
        $mapIconAttributes = collect();
        foreach ($resultEvents as $resultEvent) {
            if ($resultEvent instanceof MapChangeResultEvent) {
                $currentFloor = $resultEvent->getFloor();
                continue;
            } else if ($currentFloor === null) {
                continue;
            } else if ($resultEvent instanceof ChallengeModeEndResultEvent) {
                break;
            } else if (!($resultEvent->getBaseEvent() instanceof AdvancedCombatLogEvent)) {
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
                    '%s: source (%s): %s -> %s @ %s,%s',
                    $combatLogEvent->getTimestamp()->toDateTimeString('millisecond'),
                    $sourceGuid->getUnitType(),
                    $sourceGuid->getGuid(),
                    $combatLogEvent->getGenericData()->getSourceName(),
                    $combatLogEvent->getAdvancedData()->getPositionX(),
                    $combatLogEvent->getAdvancedData()->getPositionY(),
                );
            } else if ($destGuid instanceof Creature) {
                $comment = sprintf(
                    '%s: dest (%s): %s -> %s @ %s,%s',
                    $combatLogEvent->getTimestamp()->toDateTimeString('millisecond'),
                    $destGuid->getUnitType(),
                    $destGuid->getGuid(),
                    $combatLogEvent->getGenericData()->getDestName(),
                    $combatLogEvent->getAdvancedData()->getPositionX(),
                    $combatLogEvent->getAdvancedData()->getPositionY(),
                );
            }

            $mapIconAttributes->push([
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
        }

        MapIcon::insert($mapIconAttributes->toArray());
    }
}