<?php

namespace App\Service\MDT\Import;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Exception\ImportError;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\Structs\LatLng;
use App\Models\Arrow;
use App\Models\Brushline;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Path;
use App\Models\Polyline;
use App\Models\Spell\Spell;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Logging\MDTImportStringServiceLoggingInterface;
use App\Service\MDT\Models\ImportStringObjects;
use Illuminate\Database\Eloquent\Builder;

class ObjectImporter
{
    /** @var int */
    private const int IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS = 50;

    public function __construct(
        private readonly CoordinatesServiceInterface            $coordinatesService,
        private readonly MDTImportStringServiceLoggingInterface $log,
    ) {
    }

    /**
     * Parse any saved objects from the MDT string to a $dungeonRoute, optionally $save'ing the objects to the database.
     */
    public function parseObjects(
        ImportStringObjects $importStringObjects,
        bool                $assignNotesToPulls,
    ): ImportStringObjects {
        if (count($importStringObjects->getMdtObjects()) > config('keystoneguru.dungeon_route_limits.map_icons')) {
            $importStringObjects->getErrors()->push(
                new ImportError(
                    __('services.mdt.io.import_string.category.notes'),
                    __('services.mdt.io.import_string.limit_reached_notes', ['limit' => config('keystoneguru.dungeon_route_limits.map_icons')]),
                ),
            );

            return $importStringObjects;
        }

        $mappingVersion = $importStringObjects->getDungeon()->getCurrentMappingVersion();

        $floors = $importStringObjects->getDungeon()->floorsForMapFacade(
            $mappingVersion,
            $mappingVersion->facade_enabled,
        )->get();

        foreach ($importStringObjects->getMdtObjects() as $objectIndex => $object) {
            try {
                /*
                 * Note
                 * 1 = x (size in case of line)
                 * 2 = y (smooth in case of line)
                 * 3 = sublevel
                 * 4 = enabled/visible?
                 * 5 = text (color in case of line)
                 *
                 * Line
                 * 1 = size (weight?)
                 * 2 = linefactor
                 * 3 = sublevel
                 * 4 = enabled/visible?
                 * 5 = color
                 * 6 = drawlayer
                 * 7 = smooth
                 *
                 * Triangle
                 * 1 = rotation (rad)
                 */
                // Fix a strange issue where 6 would sometimes not be set - and then the array may look like this:
                /** d: {
                 * 1: 3,
                 * 2: 1.1,
                 * 3: 1,
                 * 4: false,
                 * 5: "fafff9",
                 * 7: true
                 * } */
                if (!isset($object['d'][0])) {
                    if (!isset($object['d'][6])) {
                        $object['d'][6] = 0;
                    }

                    $details = array_values($object['d']);
                } else {
                    $details = $object['d'];
                }

                // Get the proper index of the floor, validated for length
                $mdtSubLevel = ((int)$details[2]);

                /** @var Floor|null $floor */
                $floor = $floors->first(static fn(
                    Floor $floor,
                ) => ($floor->mdt_sub_level ?? $floor->index) === $mdtSubLevel);

                if ($floor === null) {
                    throw new ImportWarning(
                        sprintf(__('services.mdt.io.import_string.category.object'), $objectIndex),
                        sprintf(__('services.mdt.io.import_string.unable_to_find_floor_for_object'), $mdtSubLevel),
                        ['details' => __('services.mdt.io.import_string.unable_to_find_floor_for_object_details') . json_encode($details)],
                    );
                }

                // If not shown/visible, ignore it
                if (!$details[3]) {
                    continue;
                }

                // Triangles (t = triangle)
                // MethodDungeonTools.lua:2554
                if (isset($object['l'])) {
                    $lineCount = count($object['l']);
                    // Also, ignore lines which are less than 2 points, and those with uneven coordinates (malformed)
                    if ($lineCount >= 4 && $lineCount % 2 === 0) {
                        // Convert all the line points to LatLngs
                        $dominantFloor = null;
                        $vertices      = [];
                        for ($i = 0; $i < $lineCount; $i += 2) {
                            $latLng = Conversion::convertMDTCoordinateToLatLng(
                                [
                                    'x' => floatval($object['l'][$i]),
                                    'y' => floatval($object['l'][$i + 1]),
                                ],
                                $floor,
                            );

                            if ($floor->facade) {
                                $latLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation(
                                    $mappingVersion,
                                    $latLng,
                                    $dominantFloor,
                                );

                                // Attempt to set the dominant floor, or fall back to what was set before
                                $dominantFloor ??= $latLng->getFloor();
                            }

                            $vertices[] = $latLng->toArray();
                        }

                        // Arrows
                        if (isset($object['t']) && $object['t']) {
                            $this->parseObjectTriangle($importStringObjects, $mappingVersion, $floor, $details, $vertices, $dominantFloor/*, $object['t'][0]*/);
                        }
                        // If it's a line
                        // MethodDungeonTools.lua:2529
                        else {
                            $this->parseObjectLine($importStringObjects, $mappingVersion, $floor, $details, $vertices, $dominantFloor);
                        }
                    }
                }
                // Map comment (n = note)
                // MethodDungeonTools.lua:2523
                elseif (isset($object['n']) && $object['n']) {
                    $this->parseObjectComment($importStringObjects, $mappingVersion, $floor, $details, $assignNotesToPulls);
                }
            } catch (ImportWarning $warning) {
                $importStringObjects->getWarnings()->push($warning);
            }
        }

        return $importStringObjects;
    }

    private function parseObjectTriangle(
        ImportStringObjects $importStringObjects,
        MappingVersion      $mappingVersion,
        Floor               $floor,
        array               $details,
        array               $vertices,
        ?Floor              $dominantFloor = null,
    ): void {
        $weight = min(5, max(1, (int)$details[0]));

        $importStringObjects->getArrows()->push([
            'floor_id' => ($dominantFloor ?? $floor)->id,
            'polyline' => [
                'color'         => (!str_starts_with((string)$details[4], '#') ? '#' : '') . $details[4],
                'weight'        => $weight,
                'vertices_json' => json_encode($vertices),
                'model_class'   => Arrow::class,
            ],
        ]);

        if ($importStringObjects->getArrows()->count() > config('keystoneguru.dungeon_route_limits.arrows')) {
            $importStringObjects->getErrors()->push(
                new ImportError(
                    __('services.mdt.io.import_string.category.arrows'),
                    __('services.mdt.io.import_string.limit_reached_arrows', ['limit' => config('keystoneguru.dungeon_route_limits.arrows')]),
                ),
            );
        }
    }

    private function parseObjectLine(
        ImportStringObjects $importStringObjects,
        MappingVersion      $mappingVersion,
        Floor               $floor,
        array               $details,
        array               $vertices,
        ?Floor              $dominantFloor = null,
    ): void {
        $isFreeDrawn = isset($details[6]) && $details[6];

        // Between 1 and 5
        $weight = min(5, max(1, (int)$details[0]));

        $lineOrPathAttribute = [
            'floor_id' => ($dominantFloor ?? $floor)->id,
            'polyline' => [
                // Make sure there is a pound sign in front of the value at all times, but never double up should
                // MDT decide to suddenly place it here
                'color'         => (!str_starts_with((string)$details[4], '#') ? '#' : '') . $details[4],
                'weight'        => $weight,
                'vertices_json' => json_encode($vertices),
                // To be set later
                // 'model_id' => ?,
                'model_class' => $isFreeDrawn ? Brushline::class : Path::class,
            ],
        ];

        if ($isFreeDrawn) {
            $importStringObjects->getLines()->push($lineOrPathAttribute);

            if ($importStringObjects->getLines()->count() > config('keystoneguru.dungeon_route_limits.brushlines')) {
                $importStringObjects->getErrors()->push(
                    new ImportError(
                        __('services.mdt.io.import_string.category.brushlines'),
                        __('services.mdt.io.import_string.limit_reached_brushlines', ['limit' => config('keystoneguru.dungeon_route_limits.brushlines')]),
                    ),
                );
            }
        } else {
            $importStringObjects->getPaths()->push($lineOrPathAttribute);

            if ($importStringObjects->getPaths()->count() > config('keystoneguru.dungeon_route_limits.paths')) {
                $importStringObjects->getErrors()->push(
                    new ImportError(
                        __('services.mdt.io.import_string.category.paths'),
                        __('services.mdt.io.import_string.limit_reached_paths', ['limit' => config('keystoneguru.dungeon_route_limits.paths')]),
                    ),
                );
            }
        }
    }

    private function parseObjectComment(
        ImportStringObjects $importStringObjects,
        MappingVersion      $mappingVersion,
        Floor               $floor,
        array               $details,
        bool                $assignNotesToPulls,
    ): void {
        $latLng = Conversion::convertMDTCoordinateToLatLng([
            'x' => $details[0],
            'y' => $details[1],
        ], $floor);

        if ($floor->facade) {
            $latLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation(
                $mappingVersion,
                $latLng,
            );

            // @TODO this needs to be put everywhere in this class in a generic function of sorts, no time now
            if ($latLng->getFloor()->facade) {
                $this->log->parseObjectCommentAfterConversionFloorStillOnFacade($latLng->toArrayWithFloor());

                $importStringObjects->getWarnings()->push(
                    new ImportWarning(
                        __('services.mdt.io.import_string.category.object'),
                        __('services.mdt.io.import_string.object_out_of_bounds', ['comment' => (string)$details['4']]),
                    ),
                );

                return;
            }
        }

        $ingameXY = $this->coordinatesService->calculateIngameLocationForMapLocation($latLng);

        // Try to see if we can import this comment and apply it to our pulls directly instead
        foreach ($importStringObjects->getKillZoneAttributes() as $killZoneIndex => $killZoneAttribute) {
            foreach ($killZoneAttribute['killZoneEnemies'] as $killZoneEnemy) {
                $enemyIngameXY = $this->coordinatesService->calculateIngameLocationForMapLocation(
                    new LatLng($killZoneEnemy['enemy']->lat, $killZoneEnemy['enemy']->lng, $latLng->getFloor()),
                );

                if ($this->coordinatesService->distanceBetweenPoints(
                    $enemyIngameXY->getX(),
                    $ingameXY->getX(),
                    $enemyIngameXY->getY(),
                    $ingameXY->getY(),
                ) < self::IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS) {
                    $bloodLustNames = [
                        'bloodlust',
                        'heroism',
                        'fury of the ancients',
                        'time warp',
                        'timewarp',
                        'ancient hysteria',
                    ];

                    // If the user wants to put heroism/bloodlust on this pull, directly assign it instead
                    $commentLower = strtolower(trim((string)$details[4]));
                    if (in_array($commentLower, $bloodLustNames)) {
                        $spellId = 0;

                        if ($commentLower === 'bloodlust') {
                            $spellId = Spell::SPELL_BLOODLUST;
                        } elseif ($commentLower === 'heroism') {
                            $spellId = Spell::SPELL_HEROISM;
                        } elseif ($commentLower === 'fury of the aspects') { // @phpstan-ignore identical.alwaysFalse
                            $spellId = Spell::SPELL_FURY_OF_THE_ASPECTS;
                        } elseif ($commentLower === 'time warp' || $commentLower === 'timewarp') {
                            $spellId = Spell::SPELL_TIME_WARP;
                        } elseif ($commentLower === 'ancient hysteria') {
                            $spellId = Spell::SPELL_ANCIENT_HYSTERIA;
                        } elseif ($commentLower === 'drums') { // @phpstan-ignore identical.alwaysFalse
                            $spellId = Spell::SPELL_THUNDEROUS_DRUMS;
                        } elseif ($commentLower === 'primal rage') { // @phpstan-ignore identical.alwaysFalse
                            $spellId = Spell::SPELL_PRIMAL_RAGE;
                        } elseif ($commentLower === 'harriers cry') { // @phpstan-ignore identical.alwaysFalse
                            $spellId = Spell::SPELL_HARRIERS_CRY;
                        }

                        $newAttributes = $killZoneAttribute['spells'][] = [
                            'spell_id' => $spellId,
                        ];
                    } elseif ($assignNotesToPulls) {
                        // Add it as a comment instead
                        $newAttributes = ['description' => $details[4]];
                    }

                    // If a description was already set and we're trying to set it again..
                    if (empty($newAttributes) || (!empty($killZoneAttribute['description']) && !empty($newAttributes['description']))) {
                        // Tough luck - the pull was already assigned a description, can't do it again
                        // But do render them on the map as usual
                        break 2;
                    }

                    // Set description directly on the object
                    $importStringObjects->getKillZoneAttributes()->put(
                        $killZoneIndex,
                        array_merge($killZoneAttribute, $newAttributes),
                    );

                    // Map icon was assigned to killzone instead - return, we're done
                    return;
                }
            }
        }

        $importStringObjects->getMapIcons()->push(array_merge([
            'mapping_version_id' => null,
            'floor_id'           => $latLng->getFloor()->id,
            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT],
            'comment'            => $details[4],
        ], $latLng->toArray()));
    }

    public function applyObjectsToDungeonRoute(
        ImportStringObjects $importStringObjects,
        DungeonRoute        $dungeonRoute,
    ): void {
        $now = now();

        /**
         * Each entry maps an ImportStringObjects collection to its model class and DungeonRoute relation name.
         * This unified loop replaces the previous copy-pasted brushline/path blocks.
         *
         * @var \Illuminate\Support\Collection<int, array{objects: \Illuminate\Support\Collection, model: class-string, relation: string}> $typedObjects
         */
        $typedObjects = collect([
            ['objects' => $importStringObjects->getLines(),  'model' => Brushline::class, 'relation' => 'brushlines'],
            ['objects' => $importStringObjects->getPaths(),  'model' => Path::class,      'relation' => 'paths'],
            ['objects' => $importStringObjects->getArrows(), 'model' => Arrow::class,     'relation' => 'arrows'],
        ]);

        $polyLinesAttributes = [];

        // 1. Insert each model type and collect polyline attributes in insertion order
        foreach ($typedObjects as $type) {
            $modelRows = [];

            foreach ($type['objects'] as $obj) {
                $modelRows[] = [
                    'dungeon_route_id' => $dungeonRoute->id,
                    'floor_id'         => $obj['floor_id'],
                    'polyline_id'      => -1,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];
                $polyLinesAttributes[] = $obj['polyline'];
            }

            if (!empty($modelRows)) {
                $type['model']::insert($modelRows);
            }
        }

        // 2. Reload all three relations at once so we know their IDs
        $dungeonRoute->load($typedObjects->map(fn($type) => $type['relation'])->values()->toArray());

        // 3. Assign model_id to each polyline attribute using the same insertion order
        $polyLineIndex = 0;
        foreach ($typedObjects as $type) {
            foreach ($dungeonRoute->getRelation($type['relation']) as $model) {
                $polyLinesAttributes[$polyLineIndex]['model_id'] = $model->id;
                $polyLineIndex++;
            }
        }

        Polyline::insert($polyLinesAttributes);

        // 4. Query back the inserted polylines and link them to their owners
        $polyLines = Polyline::where(static function (Builder $builder) use ($dungeonRoute, $typedObjects) {
            foreach ($typedObjects as $type) {
                $builder->orWhere(static function (Builder $builder) use ($dungeonRoute, $type) {
                    $builder->whereIn('model_id', $dungeonRoute->getRelation($type['relation'])->pluck('id'))
                        ->where('model_class', $type['model']);
                });
            }
        })->orderBy('id')
            ->get('id');

        $polyLineIndex = 0;
        foreach ($typedObjects as $type) {
            foreach ($dungeonRoute->getRelation($type['relation']) as $model) {
                $model->update(['polyline_id' => $polyLines->get($polyLineIndex)->id]);
                $polyLineIndex++;
            }
        }

        // Assign map objects to the route
        $mapIconsAttributes = [];
        foreach ($importStringObjects->getMapIcons() as $mapIcon) {
            $mapIconsAttributes[] = array_merge($mapIcon, [
                'dungeon_route_id' => $dungeonRoute->id,
            ]);
        }

        MapIcon::insert($mapIconsAttributes);
    }
}
