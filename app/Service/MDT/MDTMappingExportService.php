<?php

namespace App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\Structs\LatLng;
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
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Logging\MDTMappingExportServiceLoggingInterface;
use App\Service\MDT\Lua\LuaLiteral;
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
     * The order we emit the `MDT.<name>[dungeonIndex]` assignments in when there is no existing MDT file
     * to take the order from.
     */
    private const array SECTION_ORDER = [
        'dungeonMaps',
        'dungeonSubLevels',
        'dungeonTotalCount',
        'mapPOIs',
        'dungeonEnemies',
    ];

    /**
     * {@inheritDoc}
     */
    public function getMDTMappingAsLuaString(
        MappingVersion $mappingVersion,
        bool           $excludeTranslations = false,
        bool           $forceEnemyPatrols = false,
        ?string        $preserveFromFilePath = null,
        bool           $regenerateMapPOIs = false,
    ): string {
        $preservedContent = $preserveFromFilePath === null ?
            null :
            MDTMappingExportPreservedContent::fromFile($preserveFromFilePath);

        $translations = collect();

        $sections = [
            'dungeonMaps'       => $this->getDungeonMaps($mappingVersion),
            'dungeonSubLevels'  => $this->getDungeonSubLevels($mappingVersion, $translations, $preservedContent),
            'dungeonTotalCount' => $this->getDungeonTotalCount($mappingVersion),
            'mapPOIs'           => $this->getMapPOIs($mappingVersion, $preservedContent, $regenerateMapPOIs),
            'dungeonEnemies'    => $this->getDungeonEnemies($mappingVersion, $translations, $forceEnemyPatrols, $preservedContent),
        ];

        // The header must be generated last - the sections above push the translations it renders
        $result = $this->getHeader($mappingVersion, $translations, $excludeTranslations, $preservedContent);

        foreach ($this->getSectionOrder($preservedContent) as $section) {
            $result .= $sections[$section];
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private function getSectionOrder(?MDTMappingExportPreservedContent $preservedContent): array
    {
        $preservedOrder = array_values(array_intersect($preservedContent?->getSectionOrder() ?? [], self::SECTION_ORDER));

        return [...$preservedOrder, ...array_values(array_diff(self::SECTION_ORDER, $preservedOrder))];
    }

    /**
     * @param Collection<int, string> $translations
     */
    private function getHeader(
        MappingVersion                    $mappingVersion,
        Collection                        $translations,
        bool                              $excludeTranslations = false,
        ?MDTMappingExportPreservedContent $preservedContent = null,
    ): string {
        $translations->push(__($mappingVersion->dungeon->name));

        $translationsLua = $excludeTranslations ? '' : $this->getTranslations($translations);

        $zoneIds = $mappingVersion->dungeon->floors()
            ->where('facade', 0)
            ->get(['ui_map_id'])
            ->pluck('ui_map_id')
            ->toArray();

        $dungeonNameTranslationKey = $this->convertStringToTranslationKey(__($mappingVersion->dungeon->name, [], 'en_US'));

        // MDT's translation keys are hand written and not consistent between dungeons - L["Kings' Rest"]
        // sits next to L["MurderRow"] - so whatever it already uses always wins over what we would generate
        $dungeonListValue = $preservedContent?->getDungeonListValue() ??
            new LuaLiteral(sprintf('L["%s"]', $dungeonNameTranslationKey));
        $shortNameValue = $preservedContent?->getMapInfoValue('shortName') ??
            new LuaLiteral(sprintf('L["%sShortName"]', $dungeonNameTranslationKey));

        $mapInfo = array_filter([
            'teleportId' => $preservedContent?->getMapInfoValue('teleportId'),
            'iconId'     => $preservedContent?->getMapInfoValue('iconId'),
            'shortName'  => $shortNameValue,
            // Unlike the L[..] keys around it this is a plain display string rather than an index into MDT's
            // locale files, so it is ours to generate - a rename showing up in the diff is the point
            'englishName' => new LuaLiteral(sprintf('"%s"', $this->escapeLuaString(__($mappingVersion->dungeon->name, [], 'en_US')))),
            // MDT's mapID is the challenge mode ID, not our map_id
            'mapID' => new LuaLiteral((string)$mappingVersion->dungeon->challenge_mode_id),
        ], static fn(?LuaLiteral $value) => $value !== null);

        $mapInfoLua = implode(',' . PHP_EOL, array_map(
            static fn(string $key, LuaLiteral $value) => sprintf('  %s = %s', $key, $value->getLiteral()),
            array_keys($mapInfo),
            array_values($mapInfo),
        ));

        return sprintf(
            'local _, MDT = ...
local addonName = MDT.AddonName
local L = MDT.L
%slocal dungeonIndex = %d
MDT.dungeonList[dungeonIndex] = %s
MDT.mapInfo[dungeonIndex] = {
%s
};

local zones = { %s }
for _, zone in ipairs(zones) do
  MDT.zoneIdToDungeonIdx[zone] = dungeonIndex
end
',
            $translationsLua,
            $mappingVersion->dungeon->mdt_id,
            $dungeonListValue->getLiteral(),
            $mapInfoLua,
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
                    // MDT keeps a dungeon's textures in the folder of the expansion IT ships it under, which
                    // is not necessarily the expansion we have it in - King's Rest is BFA to us, Midnight to MDT
                    Conversion::getMDTExpansionName($mappingVersion->dungeon->key),
                    Conversion::getMDTDungeonName($mappingVersion->dungeon->key),
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
}
', implode(PHP_EOL, $dungeonMaps));
    }

    /**
     * @param Collection<int, string> $translations
     */
    private function getDungeonSubLevels(
        MappingVersion                    $mappingVersion,
        Collection                        $translations,
        ?MDTMappingExportPreservedContent $preservedContent = null,
    ): string {
        $subLevels = [];
        $index     = 0;
        if ($mappingVersion->facade_enabled) {
            /** @var Floor $facadeFloor */
            $facadeFloor = $mappingVersion->dungeon->floors()->firstWhere('facade', true);
            $subLevels[] = $this->getDungeonSubLevel(++$index, $facadeFloor, $preservedContent);
        } else {
            foreach ($mappingVersion->dungeon->floors()->active()->where('facade', false)->get() as $floor) {
                $subLevels[] = $this->getDungeonSubLevel(++$index, $floor, $preservedContent);
                $translations->push(__($floor->name));
            }
        }

        return sprintf('
MDT.dungeonSubLevels[dungeonIndex] = {
%s
}
', implode(PHP_EOL, $subLevels));
    }

    private function getDungeonSubLevel(
        int                               $index,
        Floor                             $floor,
        ?MDTMappingExportPreservedContent $preservedContent = null,
    ): string {
        // As with the dungeon name, MDT's sublevel translation keys are hand written - keep whatever it has
        $value = $preservedContent?->getSubLevelValue($index) ??
            new LuaLiteral(sprintf('L["%s"]', $this->convertStringToTranslationKey(__($floor->name, [], 'en_US'))));

        return sprintf('  [%d] = %s,', $index, $value->getLiteral());
    }

    private function getDungeonTotalCount(MappingVersion $mappingVersion): string
    {
        return sprintf(
            '
MDT.dungeonTotalCount[dungeonIndex] = { normal = %d }
',
            $mappingVersion->enemy_forces_required <= 0 ? 300 : $mappingVersion->enemy_forces_required,
        );
    }

    private function getMapPOIs(
        MappingVersion                    $mappingVersion,
        ?MDTMappingExportPreservedContent $preservedContent = null,
        bool                              $regenerateMapPOIs = false,
    ): string {
        // MDT's POIs are curated by hand and hold types we do not model at all (genericItem,
        // genericAssignablePOI), so by default they are kept exactly as they are. Regenerating them is
        // opt in - it is how a genuine graveyard or entrance change gets pushed, and how a dungeon MDT
        // has nothing for gets its first set.
        $preservedMapPOIs = $regenerateMapPOIs ? null : $preservedContent?->getMapPOIs();
        if ($preservedMapPOIs !== null) {
            return $this->renderMapPOIs($preservedMapPOIs);
        }

        $mapPOIs = [];

        /** @var Collection<int, Floor> $floors */
        $floors = $mappingVersion->dungeon->floorsForMapFacade($mappingVersion, false)->get();

        $subLevelOverride = $mappingVersion->facade_enabled ? 1 : null;
        foreach ($floors as $floor) {
            $subLevel = $subLevelOverride ?? $floor->mdt_sub_level ?? $floor->index;
            // With a facade every floor collapses onto sublevel 1, so keep appending rather than start over
            $mapPOIsOnFloor = $mapPOIs[$subLevel] ?? [];
            $mapPOIIndex    = count($mapPOIsOnFloor);

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
                    $mapPOIsOnFloor[++$mapPOIIndex] = [
                        'template' => 'MapLinkPinTemplate',
                        'type'     => 'mapLink',
                        ...$this->getMapPOICoordinate($dungeonFloorSwitchMarker->getLatLng()),
                        'target'    => $dungeonFloorSwitchMarker->targetFloor->mdt_sub_level ?? $dungeonFloorSwitchMarker->targetFloor->index,
                        'direction' => $dungeonFloorSwitchMarker->getMdtDirection(),
                        // @TODO this is wrong?
                        'connectionIndex' => $mapPOIIndex,
                    ];
                }
            }

            // Export graveyards and dungeon entrances
            foreach ($floor->mapIcons($mappingVersion)->with('floor')->whereIn('map_icon_type_id', [
                MapIconType::ALL[MapIconType::MAP_ICON_TYPE_GRAVEYARD],
                MapIconType::ALL[MapIconType::MAP_ICON_TYPE_DUNGEON_START],
            ])->get() as $mapIcon) {
                /** @var MapIcon $mapIcon */
                $type = $mapIcon->map_icon_type_id === MapIconType::ALL[MapIconType::MAP_ICON_TYPE_GRAVEYARD] ?
                    'graveyard' :
                    'dungeonEntrance';

                $mapPOIsOnFloor[++$mapPOIIndex] = array_filter([
                    'type' => $type,
                    ...$this->getMapPOICoordinate($mapIcon->getLatLng()),
                    'graveyardDescription' => $type === 'graveyard' ? ($mapIcon->comment ?? '') : null,
                    // Every dungeonEntrance MDT has is sized up like this, and nothing else ever is
                    'sizeMult' => $type === 'dungeonEntrance' ? 1.5 : null,
                ], static fn($value) => $value !== null);
            }

            $mapPOIs[$subLevel] = $mapPOIsOnFloor;
        }

        return $this->renderMapPOIs(array_filter($mapPOIs));
    }

    /**
     * @param array<int, mixed> $mapPOIs
     */
    private function renderMapPOIs(array $mapPOIs): string
    {
        if ($mapPOIs === []) {
            return '
MDT.mapPOIs[dungeonIndex] = {};
';
        }

        return new PhpArray2LuaTable()->toLuaTableString('MDT.mapPOIs[dungeonIndex]', $mapPOIs);
    }

    /**
     * @return array{x: float, y: float}
     */
    private function getMapPOICoordinate(LatLng $latLng): array
    {
        $rounded = Conversion::convertLatLngToMDTCoordinate($latLng);

        return ['x' => $rounded['x'], 'y' => $rounded['y']];
    }

    /**
     * @param  array<int|string, mixed>|null                   $preservedClone MDT's clone for this enemy, if it still has one.
     * @return array{x: float|LuaLiteral, y: float|LuaLiteral}
     */
    private function getEnemyCoordinate(Enemy $enemy, LatLng $latLng, ?array $preservedClone = null): array
    {
        if ($enemy->mdt_x !== null && $enemy->mdt_y !== null) {
            return ['x' => (float)$enemy->mdt_x, 'y' => (float)$enemy->mdt_y];
        }

        // The enemy did not move, we just cannot store where it is precisely enough - keep MDT's coordinate
        if ($preservedClone !== null &&
            $preservedClone['x'] instanceof LuaLiteral &&
            $preservedClone['y'] instanceof LuaLiteral) {
            return ['x' => $preservedClone['x'], 'y' => $preservedClone['y']];
        }

        $rounded = Conversion::convertLatLngToMDTCoordinate($latLng);

        return ['x' => $rounded['x'], 'y' => $rounded['y']];
    }

    /**
     * @param  array<int|string, mixed>      $array
     * @return array<int|string, mixed>|null Null when the array is empty, so it is filtered out entirely
     *                                       rather than exported as an empty table MDT never writes itself.
     */
    private function emptyToNull(array $array): ?array
    {
        return $array === [] ? null : $array;
    }

    /**
     * Takes a mapping version and outputs an array in the way MDT would read it
     * @param Collection<int, string> $translations
     */
    private function getDungeonEnemies(
        MappingVersion                    $mappingVersion,
        Collection                        $translations,
        bool                              $forceEnemyPatrols = false,
        ?MDTMappingExportPreservedContent $preservedContent = null,
    ): string {
        $dungeonEnemies = [];

        /** @var Collection<int, Npc> $npcs */
        $npcs = Npc::with(['npcEnemyForces', 'type', 'characteristics', 'spells', 'npcHealths'])
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
            if ($enemyPack->group !== null) { // @phpstan-ignore notIdentical.alwaysTrue
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

        // Keep MDT's own NPC order so a newly added NPC lands at the end of the file instead of shifting
        // every index below it - which would rewrite the whole file for a single new enemy
        if ($preservedContent !== null) {
            $enemiesByNpcId = $enemiesByNpcId->sortBy(
                static fn(Collection $enemies, $npcId) => $preservedContent->getEnemyIndex((int)$npcId) ?? PHP_INT_MAX,
            );
        }

        foreach ($enemiesByNpcId as $npcId => $enemies) {
            /** @var Collection<int, Enemy> $enemies */
            if (empty($npcId)) {
                $this->log->getDungeonEnemiesEnemiesWithoutNpcIdFound($enemies->pluck('id')->toArray());

                continue;
            }

            // Ensure that if new enemies are added they are added last and not first - this helps a lot with assigning new IDs
            $enemies = $enemies->sort(static fn(
                Enemy $a,
                Enemy $b,
            ) => $a->mdt_id === null || $b->mdt_id === null ? -1 : $a->mdt_id <=> $b->mdt_id);
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

            $npcHealth = $npc->getHealthByGameVersion($mappingVersion->gameVersion);
            // Key order follows MDT's own Developer/Schema.lua so a re-export does not shuffle the file
            $dungeonEnemy = array_filter([
                'name'   => __($npc->name, [], 'en_US'),
                'id'     => $npc->id,
                'count'  => $enemyForces,
                'health' => $npcHealth?->health ?? 123456, // @phpstan-ignore nullsafe.neverNull
                // Flags MDT curates and we have no equivalent for at all
                'ignoreFortified' => $preservedContent?->getEnemyValue($npc->id, 'ignoreFortified'),
                'scale'           => $npc->mdt_scale ?? $scaleMapping[$npc->classification_id],
                'neutral'         => $preservedContent?->getEnemyValue($npc->id, 'neutral'),
                'stealth'         => $preservedContent?->getEnemyValue($npc->id, 'stealth'),
                'stealthDetect'   => $preservedContent?->getEnemyValue($npc->id, 'stealthDetect') ??
                    ($npc->truesight ? true : null),
                'displayId'    => $npc->display_id,
                'iconTexture'  => $preservedContent?->getEnemyValue($npc->id, 'iconTexture'),
                'creatureType' => $npc->type->type,
                'level'        => $npc->level,
                // Whether MDT considers an NPC a boss is its own editorial call - the three parts of a council
                // encounter are all bosses to MDT but elites to us - so never overrule what it already decided
                'isBoss'      => $preservedContent?->getEnemyValue($npc->id, 'isBoss') ?? $isBoss,
                'encounterID' => $npc->encounter_id,
                // $npc->dungeon may be null if dungeon_id = -1
                'instanceID' => $preservedContent?->getEnemyValue($npc->id, 'instanceID') ??
                    ($isBoss ? $mappingVersion->dungeon->instance_id : null),
                'powers' => $preservedContent?->getEnemyValue($npc->id, 'powers'),
                // Characteristics are combat log derived on our side and empty outside production, so MDT's
                // own curated list wins whenever it has one
                'characteristics' => $preservedContent?->getEnemyValue($npc->id, 'characteristics') ??
                    $this->emptyToNull(
                        $npc->characteristics->mapWithKeys(fn(Characteristic $characteristic) => [__($characteristic->name, [], 'en_US') => true])->toArray(),
                    ),
                // Our own npc_spells are the NPC Compendium's data - a rolling window filled by combat log
                // parsing - and are deliberately not handed to MDT. Whatever MDT curated stays as it is.
                'spells' => $preservedContent?->getEnemyValue($npc->id, 'spells'),
                'clones' => [],
            ], fn($value) => $value !== null);

            $translations->push(__($npc->name, [], 'en_US'));

            // MDT's clone indices are not contiguous - deleting a clone leaves a gap that it keeps forever -
            // and enemies.mdt_id is exactly that index. Renumbering them would rewrite every clone after
            // the first gap, so keep MDT's own index and only hand out new ones to enemies it never had.
            $cloneIndex = (int)$enemies->max('mdt_id');
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

                $enemyCloneIndex      = $enemy->mdt_id ?? ++$cloneIndex;
                $convertedEnemyLatLng = $this->coordinatesService->convertMapLocationToFacadeMapLocation($mappingVersion, $enemy->getLatLng());
                $unroundedCoordinate  = Conversion::convertLatLngToMDTCoordinateUnrounded($convertedEnemyLatLng);
                $preservedClone       = $enemy->npc_id === null ? null : $preservedContent?->getMatchingClone(
                    $enemy->npc_id,
                    $unroundedCoordinate['x'],
                    $unroundedCoordinate['y'],
                );
                $mdtCoordinate = $this->getEnemyCoordinate($enemy, $convertedEnemyLatLng, $preservedClone);

                // An enemy that moved has no positional match, but its patrol and notes are still MDT's -
                // fall back to its index so a repositioned enemy does not silently lose them
                $preservedCloneExtras = $preservedClone ?? ($enemy->npc_id === null ?
                    null :
                    $preservedContent?->getCloneByIndex($enemy->npc_id, $enemyCloneIndex));

                // Key order follows MDT's own Developer/Schema.lua
                $dungeonEnemy['clones'][$enemyCloneIndex] = array_filter([
                    'x' => $mdtCoordinate['x'],
                    'y' => $mdtCoordinate['y'],
                    'g' => $group ?? null,
                    // Facade means that the sublevel is ALWAYS 1 since there's only one MDT level
                    'sublevel' => $enemy->floor->mdt_sub_level ?? $mappingVersion->facade_enabled ? 1 : $enemy->floor->index,
                    'scale'    => $enemy->mdt_scale,
                    'note'     => $preservedCloneExtras['note'] ?? null,
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

                    $dungeonEnemy['clones'][$enemyCloneIndex]['patrol'] = $patrolVertices;

                    // Cache it only if the patrol was tied to a group
                    if ($enemy->enemy_pack_id !== null) {
                        $savedEnemyPatrols->put($enemy->enemy_patrol_id, $enemy->enemyPatrol);
                    }
                } elseif (isset($preservedCloneExtras['patrol'])) {
                    // We could not produce a patrol for this enemy, but MDT has one - dropping it would delete
                    // a patrol from MDT that we simply failed to round trip
                    $dungeonEnemy['clones'][$enemyCloneIndex]['patrol'] = $preservedCloneExtras['patrol'];
                }

                if (isset($preservedCloneExtras['constrained'])) {
                    $dungeonEnemy['clones'][$enemyCloneIndex]['constrained'] = $preservedCloneExtras['constrained'];
                }
            }

            // New enemies are handed an index above MDT's highest, so they may not be in index order yet
            ksort($dungeonEnemy['clones']);

            $dungeonEnemies[++$dungeonEnemyIndex] = $dungeonEnemy;
        }

        return new PhpArray2LuaTable()->toLuaTableString('MDT.dungeonEnemies[dungeonIndex]', $dungeonEnemies);
    }

    /**
     * @param Collection<int, string> $translations
     */
    private function getTranslations(Collection $translations): string
    {
        // EOL at the start
        $lua = [''];
        foreach ($translations->unique() as $translation) {
            $escaped = $this->escapeLuaString((string)$translation);
            $lua[]   = sprintf('L["%s"] = "%s"', $escaped, $escaped);
        }

        // Add another EOL at the end of it
        $lua[] = '';

        return implode(PHP_EOL, $lua);
    }

    /**
     * Escapes a string for use inside a double quoted Lua string. Not addslashes(): that also escapes the
     * apostrophe, which needs no escaping here and turns King's Rest into King\'s Rest.
     */
    private function escapeLuaString(string $str): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\"'], $str);
    }

    private function convertStringToTranslationKey(string $str): string
    {
        return preg_replace('/[^A-Za-z0-9 ]/', '', Str::studly($str));
    }
}
