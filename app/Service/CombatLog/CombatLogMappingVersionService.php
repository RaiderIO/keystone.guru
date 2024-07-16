<?php

namespace App\Service\CombatLog;

use App\Logic\CombatLog\BaseEvent;
use App\Logic\CombatLog\CombatEvents\AdvancedCombatLogEvent;
use App\Logic\CombatLog\CombatLogEntry;
use App\Logic\CombatLog\Guid\Creature;
use App\Logic\CombatLog\SpecialEvents\ChallengeModeStart;
use App\Logic\CombatLog\SpecialEvents\MapChange;
use App\Logic\CombatLog\SpecialEvents\ZoneChange;
use App\Logic\Structs\IngameXY;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcType;
use App\Service\CombatLog\Logging\CombatLogMappingVersionServiceLoggingInterface;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CombatLogMappingVersionService implements CombatLogMappingVersionServiceInterface
{
    public function __construct(private readonly CombatLogServiceInterface $combatLogService, private readonly CoordinatesServiceInterface $coordinatesService, private readonly CombatLogMappingVersionServiceLoggingInterface $log)
    {
    }

    /**
     * {@inheritDoc}
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

            $mappingVersion = $this->createMappingVersionFromCombatLog($filePath, static function (BaseEvent $parsedEvent) {
                $dungeon = null;
                // Ensure we know the dungeon and verify it
                if ($parsedEvent instanceof ChallengeModeStart) {
                    $dungeon = Dungeon::where('challenge_mode_id', $parsedEvent->getChallengeModeID())->firstOrFail();
                }

                return $dungeon;
            });
        } finally {
            $this->log->createMappingVersionFromChallengeModeEnd();
        }

        return $mappingVersion;
    }

    public function createMappingVersionFromDungeonOrRaid(string $filePath, ?MappingVersion $mappingVersion = null): ?MappingVersion
    {
        $this->log->createMappingVersionFromDungeonOrRaidStart($filePath);
        try {
            $mappingVersion = $this->createMappingVersionFromCombatLog($filePath, static function (BaseEvent $parsedEvent) {
                $dungeon = null;
                // Ensure we know the dungeon and verify it
                if ($parsedEvent instanceof ZoneChange) {
                    $dungeon = Dungeon::where('map_id', $parsedEvent->getZoneId())->firstOrFail();
                }

                return $dungeon;
            }, $mappingVersion);
        } finally {
            $this->log->createMappingVersionFromDungeonOrRaidEnd();
        }

        return $mappingVersion;
    }

    private function createMappingVersionFromCombatLog(
        string          $filePath,
        callable        $extractDungeonCallable,
        ?MappingVersion $mappingVersion = null
    ): ?MappingVersion {
        $targetFilePath = $this->combatLogService->extractCombatLog($filePath) ?? $filePath;

        $hasExistingMappingVersion = $mappingVersion !== null;

        $now            = Carbon::now();
        $mappingVersion ??= MappingVersion::create([
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
        /** @var Collection|\App\Models\Npc\Npc[] $npcs */
        $npcs = collect();

        $this->combatLogService->parseCombatLog($targetFilePath, function (int $combatLogVersion, string $rawEvent, int $lineNr) use ($extractDungeonCallable, $hasExistingMappingVersion, &$mappingVersion, &$dungeon, &$currentFloor, &$npcs) {
            $this->log->addContext('lineNr', ['combatLogVersion' => $combatLogVersion, 'rawEvent' => $rawEvent, 'lineNr' => $lineNr]);

            $combatLogEntry = (new CombatLogEntry($rawEvent));
            $parsedEvent    = $combatLogEntry->parseEvent([], $combatLogVersion);

            if ($combatLogEntry->getParsedTimestamp() === null) {
                $this->log->createMappingVersionFromCombatLogTimestampNotSet();

                return $parsedEvent;
            }

            // One way or another, enforce we extract the dungeon from the combat log
            if ($dungeon === null) {
                if (($dungeon = $extractDungeonCallable($parsedEvent)) === null) {
                    $this->log->createMappingVersionFromCombatLogSkipEntryNoDungeon();
                } else {
                    if ($hasExistingMappingVersion) {
                        $dungeon = $mappingVersion->dungeon;
                        $this->log->createMappingVersionFromCombatLogDungeonFromExistingMappingVersion($dungeon->id);
                    } else {
                        /** @var Dungeon $dungeon */
                        foreach ($dungeon->floors as $floor) {
                            if ($floor->ingame_min_x === null || $floor->ingame_min_y === null || $floor->ingame_max_x === null || $floor->ingame_max_y === null) {
                                throw new Exception(
                                    sprintf('Floor %s is not configured yet - cannot place enemies on it!', __($floor->name, [], 'en_US'))
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

                    $npcs = Npc::whereIn('dungeon_id', [-1, $dungeon->id])->get()->keyBy('id');

                    // Assign the default floor in case there's no MapChange event coming (Ara-Kara is one such?)
                    /** @var Floor $currentFloor */
                    $currentFloor = $dungeon->floors()->firstWhere('default', true);
                    $this->log->createMappingVersionFromCombatLogCurrentFloorDefaultFloor($dungeon->id, $currentFloor->id);
                }

                return $parsedEvent;
            }

            // Ensure we know the floor
            if ($parsedEvent instanceof MapChange) {
                $currentFloor = Floor::findByUiMapId($parsedEvent->getUiMapID(), $dungeon->id);
                $this->log->createMappingVersionFromCombatLogCurrentFloorFromMapChange($parsedEvent->getUiMapID(), $currentFloor->id);

            } else if ($currentFloor === null) {
                $this->log->createMappingVersionFromCombatLogSkipEntryNoFloor();

                return $parsedEvent;
            }

            if ($parsedEvent instanceof AdvancedCombatLogEvent) {
                $guid = $parsedEvent->getAdvancedData()->getInfoGuid();

                if ($guid instanceof Creature) {
                    if (!$npcs->has($guid->getId())) {
                        $this->log->createMappingVersionFromCombatLogUnableToFindNpc($currentFloor->id, $guid->getId());

                        return $parsedEvent;
                    }

                    /** @var Npc $npc */
                    $npc = $npcs->get($guid->getId());
                    if ($npc->npc_type_id === NpcType::CRITTER) {
                        $this->log->createMappingVersionFromCombatLogSkipEnemyIsCritter($currentFloor->id, $guid->getId());

                        return $parsedEvent;
                    }

                    $latLng = $this->coordinatesService->calculateMapLocationForIngameLocation(
                        new IngameXY(
                            $parsedEvent->getAdvancedData()->getPositionX(),
                            $parsedEvent->getAdvancedData()->getPositionY(),
                            $currentFloor
                        )
                    );

                    Enemy::create(array_merge([
                        'floor_id'           => $currentFloor->id,
                        'mapping_version_id' => $mappingVersion->id,
                        'npc_id'             => $guid->getId(),
                        'required'           => 0,
                        'skippable'          => 0,
                    ], $latLng->toArray()));

                    $this->log->createMappingVersionFromCombatLogNewEnemy($currentFloor->id, $guid->getId());
                }
            }

            return $parsedEvent;
        });

        if ($dungeon === null) {
            $mappingVersion->delete();
        }

        return $mappingVersion;
    }
}
