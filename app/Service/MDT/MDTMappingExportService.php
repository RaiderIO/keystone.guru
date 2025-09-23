<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Models\Characteristic;
use App\Models\DungeonFloorSwitchMarker;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcClassification;
use App\Models\Npc\NpcEnemyForces;
use App\Models\Spell\Spell;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Logging\MDTMappingExportServiceLoggingInterface;
use Illuminate\Support\Collection;
use Str;

class MDTMappingExportService implements MDTMappingExportServiceInterface
{
    public function __construct(
        private readonly CoordinatesServiceInterface             $coordinatesService,
        private readonly MDTMappingExportServiceLoggingInterface $log,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getMDTMappingAsLuaString(
        MappingVersion $mappingVersion,
        bool           $excludeTranslations = false,
        bool           $forceEnemyPatrols = false,
    ): string {
        $translations = collect();

        //        return trim($this->getDungeonEnemies($mappingVersion, $translations));

        $dungeonMaps             = $this->getDungeonMaps($mappingVersion);
        $dungeonSubLevels        = $this->getDungeonSubLevels($mappingVersion, $translations);
        $dungeonTotalCountString = $this->getDungeonTotalCount($mappingVersion);
        $mapPOIs                 = $this->getMapPOIs($mappingVersion);
        $dungeonEnemies          = $this->getDungeonEnemies($mappingVersion, $translations, $forceEnemyPatrols);
        $header                  = $this->getHeader($mappingVersion, $translations, $excludeTranslations);

        return $header . $dungeonMaps . $dungeonSubLevels . $dungeonTotalCountString . $mapPOIs . $dungeonEnemies;
    }

    private function getHeader(
        MappingVersion $mappingVersion,
        Collection     $translations,
        bool           $excludeTranslations = false,
    ): string {
        $translations->push(__($mappingVersion->dungeon->name));

        $translationsLua = $excludeTranslations ? '' : $this->getTranslations($translations);

        $zoneIds = $mappingVersion->dungeon->floors()
            ->where('facade', 0)
            ->get('ui_map_id')
            ->pluck('ui_map_id')
            ->toArray();

        $dungeonNameTranslationKey = $this->convertStringToTranslationKey(__($mappingVersion->dungeon->name, [], 'en_US'));

        return sprintf(
            'local addonName = ...
local MDT = MDT
local L = MDT.L
%slocal dungeonIndex = %d
MDT.dungeonList[dungeonIndex] = L["%s"]
MDT.mapInfo[dungeonIndex] = {
    shortname = L["%s"],
    englishName = L["%s"],
    mapID = %d,
};

local zones = { %s }
for _, zone in ipairs(zones) do
  MDT.zoneIdToDungeonIdx[zone] = dungeonIndex
end
',
            $translationsLua,
            $mappingVersion->dungeon->mdt_id,
            $dungeonNameTranslationKey,
            sprintf('%sShortName', Str::camel($dungeonNameTranslationKey)),
            addslashes(__($mappingVersion->dungeon->name, [], 'en_US')),
            $mappingVersion->dungeon->map_id,
            implode(', ', $zoneIds),
        );
    }

    private function getDungeonMaps(MappingVersion $mappingVersion): string
    {
        $dungeonMaps = [];
        if ($mappingVersion->facade_enabled) {
            /** @var Floor $facadeFloor */
            $facadeFloor   = $mappingVersion->dungeon->floors()->firstWhere('facade', true);
            $dungeonMaps[] = '  [0] = "",';
            $dungeonMaps[] = sprintf(
                '  [1] = { customTextures = \'%s\' },',
                sprintf(
                    'Interface\\\\AddOns\\\\\'..addonName..\'\\\\%s\\\\Textures\\\\%s',
                    $this->convertStringToTranslationKey(__($mappingVersion->dungeon->expansion->name, [], 'en_US')),
                    $this->convertStringToTranslationKey(__($mappingVersion->dungeon->name, [], 'en_US')),
                ),
            );
        } else {
            $index         = 0;
            $dungeonMaps[] = sprintf('  [%d] = "%s",', $index, $mappingVersion->dungeon->key);
            foreach ($mappingVersion->dungeon->floors as $floor) {
                $dungeonMaps[] = sprintf('  [%d] = "%s",', ++$index, __($floor->name, [], 'en_US'));
            }
        }

        return sprintf('
MDT.dungeonMaps[dungeonIndex] = {
%s
}', implode(PHP_EOL, $dungeonMaps));
    }

    private function getDungeonSubLevels(MappingVersion $mappingVersion, Collection $translations): string
    {
        $subLevels = [];
        $index     = 0;
        if ($mappingVersion->facade_enabled) {
            /** @var Floor $facadeFloor */
            $facadeFloor = $mappingVersion->dungeon->floors()->firstWhere('facade', true);
            $floorName   = $this->convertStringToTranslationKey(__($facadeFloor->name, [], 'en_US'));
            $subLevels[] = sprintf('  [%d] = L["%s"],', ++$index, $floorName);
        } else {
            foreach ($mappingVersion->dungeon->floors()->active()->where('facade', false)->get() as $floor) {
                $floorName   = $this->convertStringToTranslationKey(__($floor->name, [], 'en_US'));
                $subLevels[] = sprintf('  [%d] = L["%s"],', ++$index, $floorName);
                $translations->push(__($floor->name));
            }
        }

        return sprintf('
MDT.dungeonSubLevels[dungeonIndex] = {
%s
}
        ', implode(PHP_EOL, $subLevels));
    }

    private function getDungeonTotalCount(MappingVersion $mappingVersion): string
    {
        return sprintf(
            '
MDT.dungeonTotalCount[dungeonIndex] = { normal = %d, teeming = %s, teemingEnabled = true }
            ',
            $mappingVersion->enemy_forces_required <= 0 ? 300 : $mappingVersion->enemy_forces_required,
            $mappingVersion->enemy_forces_required_teeming <= 0 ? 1000 : $mappingVersion->enemy_forces_required_teeming,
        );
    }

    private function getMapPOIs(MappingVersion $mappingVersion): string
    {
        $mapPOIs = [];

        /** @var Collection<Floor> $floors */
        $floors = $mappingVersion->dungeon->floorsForMapFacade($mappingVersion, false);
        //        $floors->each(function (Floor $floor) use ($mappingVersion) {
        //            $floor->setRelation('dungeon', $mappingVersion->dungeon);
        //            $floor->load('mapIcons');
        //        });

        $subLevelOverride = $mappingVersion->facade_enabled ? 1 : null;
        foreach ($floors as $floor) {
            $mapPOIsOnFloor = [];
            $mapPOIIndex    = 0;

            // MDT does not care for switch markers when the facade is enabled, so we skip them
            if (!$mappingVersion->facade_enabled) {
                /** @var DungeonFloorSwitchMarker[] $dungeonFloorSwitchMarkers */
                $dungeonFloorSwitchMarkers = $floor->dungeonFloorSwitchMarkers($mappingVersion)
                    ->with([
                        'floor',
                        'targetFloor',
                    ])
                    ->get();

                foreach ($dungeonFloorSwitchMarkers as $dungeonFloorSwitchMarker) {
                    $mapPOIsOnFloor[++$mapPOIIndex] = array_merge([
                        'template'        => 'MapLinkPinTemplate',
                        'type'            => 'mapLink',
                        'target'          => $dungeonFloorSwitchMarker->targetFloor->mdt_sub_level ?? $dungeonFloorSwitchMarker->targetFloor->index,
                        'direction'       => $dungeonFloorSwitchMarker->getMdtDirection(),
                        'connectionIndex' => $mapPOIIndex,
                        // @TODO this is wrong?
                    ], Conversion::convertLatLngToMDTCoordinate($dungeonFloorSwitchMarker->getLatLng()));
                }
            }

            // Export graveyards and dungeon entrances
            foreach ($floor->mapIcons($mappingVersion)->whereIn('map_icon_type_id', [
                MapIconType::ALL[MapIconType::MAP_ICON_TYPE_GRAVEYARD],
                MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START],
            ])->get() as $mapIcon) {
                /** @var MapIcon $mapIcon */
                $mapPOIsOnFloor[++$mapPOIIndex] = array_merge(
                    match ($mapIcon->map_icon_type_id) {
                        MapIconType::ALL[MapIconType::MAP_ICON_TYPE_GRAVEYARD] => [
                            'template'             => 'DeathReleasePinTemplate',
                            'type'                 => 'graveyard',
                            'graveyardDescription' => $mapIcon->comment ?? '',
                        ],
                        MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START] => [
                            'template' => 'MapLinkPinTemplate',
                            'type'     => 'dungeonEntrance',
                        ],
                    },
                    Conversion::convertLatLngToMDTCoordinate($mapIcon->getLatLng()),
                );
            }

            if (!empty($mapPOIsOnFloor)) {
                $mapPOIs[$subLevelOverride ?? $floor->mdt_sub_level ?? $floor->index] = $mapPOIsOnFloor;
            }
        }

        if (empty($mapPOIs)) {
            return '
MDT.mapPOIs[dungeonIndex] = {};
';
        } else {
            return (new PhpArray2LuaTable())->toLuaTableString('MDT.mapPOIs[dungeonIndex]', $mapPOIs);
        }
    }

    /**
     * Takes a mapping version and outputs an array in the way MDT would read it
     */
    private function getDungeonEnemies(MappingVersion $mappingVersion, Collection $translations, bool $forceEnemyPatrols = false): string
    {
        $dungeonEnemies = [];

        /** @var Collection<Npc> $npcs */
        $npcs = Npc::with('npcEnemyForces')
            ->join('npc_dungeons', 'npc_dungeons.npc_id', '=', 'npcs.id')
            ->select('npcs.*')
            ->where('npc_dungeons.dungeon_id', $mappingVersion->dungeon_id)
            ->get()
            ->keyBy('id');

        // A variable for storing my enemy packs and assigning them a group numbers
        $enemyPackGroups   = collect();
        $savedEnemyPatrols = collect();

        $dungeonEnemyIndex = 0;

        $hasGroupsAlready = false;
        foreach ($mappingVersion->enemyPacks as $enemyPack) {
            if ($enemyPack->group !== null) {
                $hasGroupsAlready = true;
                break;
            }
        }

        $enemiesByNpcId = $mappingVersion
            ->enemies()
            ->with([
                'floor',
                'enemyPatrol',
                'enemyPack',
            ])
            ->get()
            ->groupBy('npc_id');

        foreach ($enemiesByNpcId as $npcId => $enemies) {
            /** @var Collection<Enemy> $enemies */
            if (empty($npcId)) {
                $this->log->getDungeonEnemiesEnemiesWithoutNpcIdFound($enemies->pluck('id')->toArray());

                continue;
            }

            // Ensure that if new enemies are added they are added last and not first - this helps a lot with assigning new IDs
            $enemies = $enemies->sort(static fn(
                Enemy $a,
                Enemy $b,
            ) => $a->mdt_id === null || $b->mdt_id === null ? -1 : $a->mdt_id > $b->mdt_id);
            /** @var Npc $npc */
            $npc = $npcs->get($npcId);

            $scaleMapping = [
                NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_NORMAL]     => 0.8,
                NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_ELITE]      => 1,
                NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS]       => 1.6,
                NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS] => 1.6,
                NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_RARE]       => 1.6,
            ];

            /** @var NpcEnemyForces|null $npcEnemyForces */
            $npcEnemyForces = $npc->enemyForcesByMappingVersion($mappingVersion->id);

            $enemyForces = 0;
            if ($npcEnemyForces !== null) {
                $enemyForces = $npcEnemyForces->enemy_forces;
                // These counts are different per mapping version so we need to correct it for MDT here
                if ($npc->isShrouded()) {
                    $enemyForces = $mappingVersion->enemy_forces_shrouded;
                } elseif ($npc->isShroudedZulGamux()) {
                    $enemyForces = $mappingVersion->enemy_forces_shrouded_zul_gamux;
                }
            }

            $isBoss = $npc->classification_id >= NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS] ?
                true : null;

            $npcHealth    = $npc->getHealthByGameVersion($mappingVersion->gameVersion);
            $dungeonEnemy = array_filter([
                'name'          => __($npc->name, [], 'en_US'),
                'id'            => $npc->id,
                'count'         => $enemyForces,
                'health'        => $npcHealth?->health ?? 123456,
                'scale'         => $npc->mdt_scale ?? $scaleMapping[$npc->classification_id],
                'stealthDetect' => $npc->truesight ? true : null,
                'displayId'     => $npc->display_id,
                'creatureType'  => $npc->type->type,
                'level'         => $npc->level,
                'isBoss'        => $isBoss,
                'encounterID'   => $npc->encounter_id,
                // $npc->dungeon may be null if dungeon_id = -1
                'instanceID'      => $isBoss ? $mappingVersion->dungeon->instance_id : null,
                'characteristics' => $npc->characteristics->mapWithKeys(function (Characteristic $characteristic) {
                    return [__($characteristic->name, [], 'en_US') => true];
                })->toArray(),
                'spells' => $npc->spells
                    ->filter(fn(Spell $spell) => !$spell->hidden_on_map)
                    ->mapWithKeys(function (Spell $spell) {
                        return [$spell->id => $spell->dispel_type === Spell::DISPEL_TYPE_ENRAGE ? ['enrage' => true] : []];
                    })->toArray(),
                'clones'           => [],
                'healthPercentage' => $npcHealth?->percentage ?? null,
            ], fn($value) => $value !== null);

            $translations->push(__($npc->name, [], 'en_US'));

            $cloneIndex = 0;
            foreach ($enemies as $enemy) {
                $group = $hasGroupsAlready ? null : $enemyPackGroups->count() + 1;
                // Individual enemies with no pack
                if ($enemy->enemy_pack_id === null) {
                    $group = null;
                } elseif ($hasGroupsAlready) {
                    $group = $enemy->enemyPack->group;
                } elseif (!$enemyPackGroups->has($enemy->enemy_pack_id)) {
                    $enemyPackGroups->put($enemy->enemy_pack_id, $enemy->enemyPack->group ?? $group);
                } else {
                    $group = $enemyPackGroups->get($enemy->enemy_pack_id);
                }

                $convertedEnemyLatLng                  = $this->coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $enemy->getLatLng());
                $mdtCoordinate                         = Conversion::convertLatLngToMDTCoordinate($convertedEnemyLatLng);
                $dungeonEnemy['clones'][++$cloneIndex] = array_filter([
                    'x' => (float)($enemy->mdt_x ?? $mdtCoordinate['x']),
                    'y' => (float)($enemy->mdt_y ?? $mdtCoordinate['y']),
                    'g' => $group ?? null,
                    // Facade means that the sublevel is ALWAYS 1 since there's only one MDT level
                    'sublevel' => $enemy->floor->mdt_sub_level ?? $mappingVersion->facade_enabled ? 1 : $enemy->floor->index,
                    'scale'    => $enemy->mdt_scale,
                ]);

                // Add patrol if any
                if ($enemy->enemy_patrol_id !== null &&
                    // @TODO creating a new patrol will cause it not to be able to be exported to MDT since mdt_npc_id and mdt_id are only set when importing MDT patrols
                    // !$savedEnemyPatrols->has($enemy->enemy_patrol_id))
                    $enemy->enemyPatrol->mdt_npc_id === $enemy->npc_id &&
                    $enemy->enemyPatrol->mdt_id === $enemy->mdt_id) {
                    $patrolVertices = [];
                    $vertexIndex    = 0;
                    // Prefer the mdt polyline if it exists (it was introduced later), otherwise use the regular polyline
                    if (!$forceEnemyPatrols && $enemy->enemyPatrol->mdtPolyline !== null) {
                        $polylineMdtXYs = $enemy->enemyPatrol->mdtPolyline
                            ->getDecodedLatLngs($enemy->floor);
                        foreach ($polylineMdtXYs as $vertexMdtLatLng) {
                            // $vertexMdtLatLng actually contains the x and y in the lng and lat keys
                            $patrolVertices[++$vertexIndex] = [
                                'x' => $vertexMdtLatLng['lng'],
                                'y' => $vertexMdtLatLng['lat'],
                            ];
                        }
                    } else {
                        // Fall back to the regular polyline that may be adjusted by us
                        $polylineLatLngs = $enemy->enemyPatrol->polyline
                            ->getDecodedLatLngs($enemy->floor);

                        foreach ($polylineLatLngs as $vertexLatLng) {
                            $convertedVertexLatLng = $this->coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $vertexLatLng);
                            $vertexMDTXY           = Conversion::convertLatLngToMDTCoordinate($convertedVertexLatLng);
                            // Reverse order
                            $patrolVertices[++$vertexIndex] = [
                                'x' => $vertexMDTXY['x'],
                                'y' => $vertexMDTXY['y'],
                            ];
                        }

                        // MDT does not save the close off of its patrols, so remove the last vertex for export
                        array_pop($patrolVertices);
                    }

                    $dungeonEnemy['clones'][$cloneIndex]['patrol'] = $patrolVertices;

                    // Cache it only if the patrol was tied to a group
                    if ($enemy->enemy_pack_id !== null) {
                        $savedEnemyPatrols->put($enemy->enemy_patrol_id, $enemy->enemyPatrol);
                    }
                }
            }

            $dungeonEnemies[++$dungeonEnemyIndex] = $dungeonEnemy;
        }

        return (new PhpArray2LuaTable())->toLuaTableString('MDT.dungeonEnemies[dungeonIndex]', $dungeonEnemies);
    }

    private function getTranslations(Collection $translations): string
    {
        // EOL at the start
        $lua = [''];
        foreach ($translations->unique() as $translation) {
            $lua[] = sprintf('L["%s"] = "%s"', addslashes((string)$translation), addslashes((string)$translation));
        }

        // Add another EOL at the end of it
        $lua[] = '';

        return implode(PHP_EOL, $lua);
    }

    private function convertStringToTranslationKey(string $str): string
    {
        return preg_replace('/[^A-Za-z0-9 ]/', '', Str::studly($str));
    }
}
