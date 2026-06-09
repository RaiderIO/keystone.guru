<?php

namespace App\Service\MDT\Import;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Exception\ImportWarning;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Path;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Polyline;
use App\Service\MDT\Models\ImportStringRiftOffsets;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RiftOffsetImporter
{
    /**
     * @throws ImportWarning
     */
    public function parseRiftOffsets(ImportStringRiftOffsets $importStringRiftOffsets): ImportStringRiftOffsets
    {
        // Build an array with a structure that makes more sense
        $rifts = $importStringRiftOffsets->getRiftOffsets()[$importStringRiftOffsets->getWeek()] ?? [];

        if (empty($rifts)) {
            return $importStringRiftOffsets;
        }

        // Loaded for the comment import
        $floorIds = $importStringRiftOffsets->getDungeon()->floors->pluck('id');

        try {
            $seasonalIndexWhere = static function (Builder $query) use ($importStringRiftOffsets) {
                $query->whereNull('seasonal_index')
                    ->orWhere('seasonal_index', $importStringRiftOffsets->getSeasonalIndex() ?? 1);
            };

            $npcIdToMapIconMapping = [
                161124 => MapIcon::where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_BRUTAL])
                    ->whereIn('floor_id', $floorIds) // Urg'roth, Brutal spire
                    ->where($seasonalIndexWhere)->firstOrFail(),
                161241 => MapIcon::where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_CURSED])
                    ->whereIn('floor_id', $floorIds) // Cursed spire
                    ->where($seasonalIndexWhere)->firstOrFail(),
                161244 => MapIcon::where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_DEFILED])
                    ->whereIn('floor_id', $floorIds) // Blood of the Corruptor, Defiled spire
                    ->where($seasonalIndexWhere)->firstOrFail(),
                161243 => MapIcon::where('map_icon_type_id', MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_ENTROPIC])
                    ->whereIn('floor_id', $floorIds) // Samh'rek, Entropic spire
                    ->where($seasonalIndexWhere)->firstOrFail(),
            ];
        } catch (Exception) {
            throw new ImportWarning(
                __('services.mdt.io.import_string.category.awakened_obelisks'),
                __('services.mdt.io.import_string.unable_to_find_awakened_obelisks'),
            );
        }

        // From the built array, construct our map icons / paths
        foreach ($rifts as $npcId => $mdtXy) {
            try {
                // Find out the floor where the NPC is standing on
                /** @var Enemy $enemy */
                $enemy = Enemy::where('npc_id', $npcId)
                    ->where('mapping_version_id', $importStringRiftOffsets->getMappingVersion()->id)
                    ->whereNotNull('enemy_pack_id')
                    ->whereIn('floor_id', $floorIds)
                    ->firstOrFail();

                /** @var MapIcon $obeliskMapIcon */
                $obeliskMapIcon = $npcIdToMapIconMapping[$npcId];

                if (isset($mdtXy['sublevel'])) {
                    throw new ImportWarning(
                        __('services.mdt.io.import_string.category.awakened_obelisks'),
                        __(
                            'services.mdt.io.import_string.unable_to_find_awakened_obelisk_different_floor',
                            ['name' => __($obeliskMapIcon->mapIconType->name)],
                        ),
                    );
                }

                $mapIconEndAttributes = array_merge([
                    'mapping_version_id' => null,
                    'floor_id'           => $enemy->floor_id,
                    'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_GATEWAY],
                    'comment'            => __($obeliskMapIcon->mapIconType->name),
                    'obelisk_map_icon'   => $obeliskMapIcon,
                    // MDT has the x and y inverted here
                ], Conversion::convertMDTCoordinateToLatLng([
                    'x' => $mdtXy['x'],
                    'y' => $mdtXy['y'],
                ], $enemy->floor)->toArray());

                $hasAnimatedLines = Auth::check() && Auth::user()->hasPatreonBenefit(PatreonBenefit::ANIMATED_POLYLINES);

                $pathAttributes = [
                    'floor_id' => $enemy->floor_id,
                    'polyline' => [
                        'model_class'    => Path::class,
                        'color'          => '#80FF1A',
                        'color_animated' => $hasAnimatedLines ? '#244812' : null,
                        'weight'         => 3,
                        'vertices_json'  => json_encode([
                            [
                                'lat' => $obeliskMapIcon->lat,
                                'lng' => $obeliskMapIcon->lng,
                            ],
                            [
                                'lat' => $mapIconEndAttributes['lat'],
                                'lng' => $mapIconEndAttributes['lng'],
                            ],
                        ]),
                    ],
                ];

                $importStringRiftOffsets->getMapIcons()->push($mapIconEndAttributes);

                $importStringRiftOffsets->getPaths()->push($pathAttributes);
            } catch (ImportWarning $warning) {
                $importStringRiftOffsets->getWarnings()->add($warning);
            }
        }

        return $importStringRiftOffsets;
    }

    public function applyRiftOffsetsToDungeonRoute(
        ImportStringRiftOffsets $importStringRiftOffsets,
        DungeonRoute            $dungeonRoute,
    ): void {
        $now = now();

        // Assign map objects to the route
        $mapIconsAttributes = [];
        foreach ($importStringRiftOffsets->getMapIcons() as $mapIcon) {
            $mapIconsAttributes[] = array_merge($mapIcon, [
                'dungeon_route_id'   => $dungeonRoute->id,
                'mapping_version_id' => $mapIcon['mapping_version_id'],
                'floor_id'           => $mapIcon['floor_id'],
                'map_icon_type_id'   => $mapIcon['map_icon_type_id'],
                'comment'            => $mapIcon['comment'],
            ]);
        }

        MapIcon::insert($mapIconsAttributes);

        $polyLinesAttributes = [];

        $pathsAttributes = [];
        foreach ($importStringRiftOffsets->getPaths() as $path) {
            $pathsAttributes[] = [
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $path['floor_id'],
                'polyline_id'      => -1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
            $polyLinesAttributes[] = $path['polyline'];
        }

        Path::insert($pathsAttributes);

        // Get only the paths that have no assigned polyline for this route
        $polyLineIndex = 0;
        $paths         = $dungeonRoute->paths()
            ->where('polyline_id', -1)
            ->orderBy('id')
            ->get();

        foreach ($paths as $path) {
            /** @var Path $path */
            $polyLinesAttributes[$polyLineIndex]['model_id'] = $path->id;
            $path->setLinkedAwakenedObeliskByMapIconId($mapIconsAttributes[$polyLineIndex]['obelisk_map_icon']->id);

            $polyLineIndex++;
        }

        Polyline::insert($polyLinesAttributes);

        // Assign the polylines back to the brushlines/paths
        $polyLines = Polyline::whereIn('model_id', $paths->pluck('id'))
            ->where('model_class', Path::class)
            ->orderBy('id')
            ->get('id');

        // Assign the polylines back to the brushlines/paths
        $polyLineIndex = 0;
        foreach ($paths as $path) {
            $path->update(['polyline_id' => $polyLines->get($polyLineIndex)->id]);

            $polyLineIndex++;
        }

        // Assign awakened obelisks
        $obeliskMapIcons = $dungeonRoute->mapicons()
            ->whereIn('map_icon_type_id', [
                MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_BRUTAL],
                MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_CURSED],
                MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_DEFILED],
                MapIconType::ALL[MapIconType::MAP_ICON_TYPE_AWAKENED_OBELISK_ENTROPIC],
            ])
            ->orderBy('id')
            ->get();

        $obeliskMapIconIndex = 0;
        foreach ($obeliskMapIcons as $obeliskMapIcon) {
            /** @var MapIcon $obeliskMapIcon */
            $obeliskMapIcon->setLinkedAwakenedObeliskByMapIconId($mapIconsAttributes[$obeliskMapIconIndex]['obelisk_map_icon']->id);

            $obeliskMapIconIndex++;
        }
    }
}
