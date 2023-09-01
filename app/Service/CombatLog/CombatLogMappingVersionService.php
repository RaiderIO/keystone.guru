<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\Logging\CombatLogMappingVersionServiceLoggingInterface;
use Carbon\Carbon;
use Exception;

class CombatLogMappingVersionService implements CombatLogMappingVersionServiceInterface
{
    private CombatLogServiceInterface $combatLogService;

    private CombatLogMappingVersionServiceLoggingInterface $log;

    /**
     * @param CombatLogServiceInterface                      $combatLogService
     * @param CombatLogMappingVersionServiceLoggingInterface $log
     */
    public function __construct(
        CombatLogServiceInterface                      $combatLogService,
        CombatLogMappingVersionServiceLoggingInterface $log
    )
    {
        $this->combatLogService = $combatLogService;
        $this->log              = $log;
    }

    /**
     * @inheritDoc
     */
    public function createMappingVersionFromChallengeMode(string $filePath): ?MappingVersion
    {
        $this->log->createMappingVersionFromChallengeModeStart($filePath);
        try {
            $targetFilePath = $this->combatLogService->extractCombatLog($filePath) ?? $filePath;

            // We don't need to do anything if there are no runs
            // If there's one run, we may still want to trim the fat of the log and keep just
            // the one challenge mode that's in there
            $challengeModeCount = $this->combatLogService->getChallengeModes($targetFilePath)->count();
            if ($challengeModeCount <= 0) {
                $this->log->createMappingVersionFromChallengeModeNoChallengeModesFound();

                return null;
            } else if ($challengeModeCount > 1) {
                $this->log->createMappingVersionFromChallengeModeMultipleChallengeModesFound();

                return null;
            }

            $mappingVersion = $this->createMappingVersionFromCombatLog($filePath, function (BaseEvent $parsedEvent) {
                $dungeon = null;

                // Ensure we know the dungeon and verify it
                if ($parsedEvent instanceof ChallengeModeStart) {
                    $dungeon = Dungeon::where('map_id', $parsedEvent->getInstanceID())->firstOrFail();
                }

                return $dungeon;
            });
        } finally {
            $this->log->createMappingVersionFromChallengeModeEnd();
        }

        return $mappingVersion;
    }

    /**
     * @param string $filePath
     *
     * @return MappingVersion|null
     */
    public function createMappingVersionFromDungeonOrRaid(string $filePath): ?MappingVersion
    {
        $this->log->createMappingVersionFromDungeonOrRaidStart($filePath);
        try {
            $mappingVersion = $this->createMappingVersionFromCombatLog($filePath, function (BaseEvent $parsedEvent) {
                $dungeon = null;

                // Ensure we know the dungeon and verify it
                if ($parsedEvent instanceof ZoneChange) {
                    $dungeon = Dungeon::where('map_id', $parsedEvent->getZoneId())->firstOrFail();
                }

                return $dungeon;
            });
        } finally {
            $this->log->createMappingVersionFromDungeonOrRaidEnd();
        }

        return $mappingVersion;
    }


    /**
     * @param string   $filePath
     * @param callable $extractDungeonCallable
     *
     * @return MappingVersion|null
     */
    private function createMappingVersionFromCombatLog(string $filePath, callable $extractDungeonCallable): ?MappingVersion
    {
        $targetFilePath = $this->combatLogService->extractCombatLog($filePath) ?? $filePath;

        $now            = Carbon::now();
        $mappingVersion = MappingVersion::create([
            'dungeon_id'            => -1,
            'version'               => 1,
            'enemy_forces_required' => 0,
            'timer_max_seconds'     => 0,
            'updated_at'            => $now,
            'created_at'            => $now,
        ]);

        /** @var Dungeon|null $dungeon */
        $dungeon = null;
        /** @var Floor|null $currentFloor */
        $currentFloor = null;
        $this->combatLogService->parseCombatLog($targetFilePath, function (string $rawEvent, int $lineNr)
        use ($targetFilePath, $extractDungeonCallable, &$mappingVersion, &$dungeon, &$currentFloor) {
            $this->log->addContext('lineNr', ['rawEvent' => $rawEvent, 'lineNr' => $lineNr]);

            $combatLogEntry = (new CombatLogEntry($rawEvent));
            $parsedEvent    = $combatLogEntry->parseEvent();

            if ($combatLogEntry->getParsedTimestamp() === null) {
                $this->log->createMappingVersionFromCombatLogTimestampNotSet();

                return;
            }

            // One way or another, enforce we extract the dungeon from the combat log
            if ($dungeon === null) {
                if (($dungeon = $extractDungeonCallable($parsedEvent)) === null) {
                    $this->log->createMappingVersionFromCombatLogSkipEntryNoDungeon();
                } else {
                    /** @var Dungeon $dungeon */
                    foreach ($dungeon->floors as $floor) {
                        if ($floor->ingame_min_x === null || $floor->ingame_min_y === null || $floor->ingame_max_x === null || $floor->ingame_max_y === null) {
                            throw new Exception(
                                sprintf('Floor %s is not configured yet - cannot place enemies on it!', __($floor->name, [], 'en'))
                            );
                        }
                    }

                    // We expect it to be 1 since we just created a mapping version
//                    if ($dungeon->mappingVersions()->count() > 1) {
//                        $mappingVersion->delete();
//
//                        throw new Exception('Unable to create initial mapping version from combat log - there are already mapping versions for this dungeon!');
//                    }

                    // If the dungeon was found, update the mapping version
                    $mostRecentMappingVersion = MappingVersion::where('dungeon_id', $dungeon->id)->orderByDesc('version')->first();

                    $newMappingVersionVersion = $mostRecentMappingVersion === null ? 1 : $mostRecentMappingVersion->version + 1;

                    $mappingVersion->update(['dungeon_id' => $dungeon->id, 'version' => $newMappingVersionVersion]);
                    $mappingVersion->setRelation('dungeon', $dungeon);
                }

                return;
            }

            // Ensure we know the floor
            if ($parsedEvent instanceof MapChange) {
                $currentFloor = Floor::findByUiMapId($parsedEvent->getUiMapID());
            } else if ($currentFloor === null) {
                $this->log->createMappingVersionFromCombatLogSkipEntryNoFloor();

                return;
            }

            if ($parsedEvent instanceof AdvancedCombatLogEvent) {
                $guid = $parsedEvent->getAdvancedData()->getInfoGuid();

                if ($guid instanceof Creature) {
                    $latLng = $currentFloor->calculateMapLocationForIngameLocation(
                        $parsedEvent->getAdvancedData()->getPositionX(),
                        $parsedEvent->getAdvancedData()->getPositionY()
                    );

                    Enemy::create(array_merge([
                        'floor_id'           => $currentFloor->id,
                        'mapping_version_id' => $mappingVersion->id,
                        'npc_id'             => $guid->getId(),
                        'required'           => 0,
                        'skippable'          => 0,
                    ], $latLng));

                    $this->log->createMappingVersionFromCombatLogNewEnemy($currentFloor->id, $guid->getId());
                }
            }
        });


        if ($dungeon === null) {
            $mappingVersion->delete();
        }

        return $mappingVersion;
    }
}
