<?php

namespace App\Service\MDT\Import;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Exception\ImportError;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\Structs\LatLng;
use App\Logic\Utils\HtmlSanitizer;
use App\Models\Arrow;
use App\Models\Brushline;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Models\Mapping\MappingVersion;
use App\Models\Path;
use App\Models\Polyline;
use App\Models\Spell\KnownSpell;
use App\Models\Spell\Spell;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Logging\MDTImportStringServiceLoggingInterface;
use App\Service\MDT\Models\ImportStringObjects;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ObjectImporter
{
    /** @var int A note closer than this to an enemy of a pull is attached to that pull instead of becoming a map icon */
    public const int IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS = 50;

    /** @var array<string, int> Names users write on MDT notes for a spell other than the spell's own name */
    private const array SPELL_IDS_BY_ALIAS = [
        'timewarp'             => KnownSpell::TimeWarp->value,
        'ancient hysteria'     => KnownSpell::AncientHysteria->value,
        'fury of the ancients' => KnownSpell::FuryOfTheAspects->value,
    ];

    /** @var array<int, string> MDT's raid marker names, by raid target index */
    private const array MDT_MAP_TEXT_RAID_MARKERS = [
        1 => 'star',
        2 => 'circle',
        3 => 'diamond',
        4 => 'triangle',
        5 => 'moon',
        6 => 'square',
        7 => 'cross',
        8 => 'skull',
    ];

    /** @var array<int, string> Map text tags MDT draws as an icon that read fine as the word itself */
    private const array MDT_MAP_TEXT_WORD_TAGS = [
        'tank',
        'healer',
        'dps',
        'death knight',
        'demon hunter',
        'druid',
        'evoker',
        'hunter',
        'mage',
        'monk',
        'paladin',
        'priest',
        'rogue',
        'shaman',
        'warlock',
        'warrior',
    ];

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

        $mappingVersion = $importStringObjects->getMappingVersion();

        $floors = $importStringObjects->getDungeon()->floorsForMapFacade(
            $mappingVersion,
            $mappingVersion->facade_enabled,
        )->get();

        /** @var array<string, int>|null $spellIdsByName */
        $spellIdsByName = null;

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
                        $latLngs = $this->convertLineToLatLngs($mappingVersion, $floor, $object['l']);

                        $isArrow = isset($object['t']) && $object['t'];

                        if ($this->isOutOfBounds(
                            $importStringObjects,
                            $objectIndex,
                            $latLngs[0],
                            __($isArrow ?
                                'services.mdt.io.import_string.arrow_out_of_bounds' :
                                'services.mdt.io.import_string.line_out_of_bounds'),
                        )) {
                            continue;
                        }

                        $vertices = array_map(static fn(LatLng $latLng) => $latLng->toArray(), $latLngs);

                        // Arrows
                        if ($isArrow) {
                            $this->parseObjectTriangle($importStringObjects, $mappingVersion, $floor, $details, $vertices, $latLngs[0]->getFloor()/*, $object['t'][0]*/);
                        }
                        // If it's a line
                        // MethodDungeonTools.lua:2529
                        else {
                            $this->parseObjectLine($importStringObjects, $mappingVersion, $floor, $details, $vertices, $latLngs[0]->getFloor());
                        }
                    }
                }
                // Map comment (n = note)
                // MethodDungeonTools.lua:2523
                elseif (isset($object['n']) && $object['n']) {
                    // A map text (tx = text) is a note whose text carries MDT's inline markup
                    if (isset($object['tx']) && $object['tx']) {
                        $details[4] = $this->convertMapTextToPlainText((string)($details[4] ?? ''));

                        if ($details[4] === '') {
                            continue;
                        }
                    }

                    $spellIdsByName ??= $this->getSpellIdsByName();

                    $this->parseObjectComment(
                        $importStringObjects,
                        $objectIndex,
                        $mappingVersion,
                        $floor,
                        $details,
                        $assignNotesToPulls,
                        $spellIdsByName,
                    );
                }
            } catch (ImportWarning $warning) {
                $importStringObjects->getWarnings()->push($warning);
            }
        }

        return $importStringObjects;
    }

    /**
     * Converts an MDT line (flat x, y pairs) to lat/lngs. On the facade, the first point that lands on a floor behind
     * it decides the floor of the whole line, and every other point is converted onto that same floor - including the
     * points before it, which would otherwise keep their facade coordinates. When no point lands on a floor, every
     * point stays on the facade.
     *
     * @param  array<int, mixed>      $line
     * @return non-empty-list<LatLng>
     */
    private function convertLineToLatLngs(MappingVersion $mappingVersion, Floor $floor, array $line): array
    {
        $mapLatLngs = [];
        for ($i = 0; $i < count($line); $i += 2) {
            $mapLatLngs[] = Conversion::convertMDTCoordinateToLatLng(
                [
                    'x' => floatval($line[$i]),
                    'y' => floatval($line[$i + 1]),
                ],
                $floor,
            );
        }

        if (!$floor->facade) {
            return $mapLatLngs;
        }

        $dominantFloor = null;
        $latLngs       = [];
        foreach ($mapLatLngs as $mapLatLng) {
            $latLng = $this->coordinatesService->convertFacadeMapLocationToMapLocation($mappingVersion, $mapLatLng, $dominantFloor);

            if (!$latLng->getFloor()?->facade) {
                $dominantFloor ??= $latLng->getFloor();
            }

            $latLngs[] = $latLng;
        }

        if ($dominantFloor !== null) {
            foreach ($latLngs as $index => $latLng) {
                if ($latLng->getFloor()?->facade) {
                    $latLngs[$index] = $this->coordinatesService->convertFacadeMapLocationToMapLocation(
                        $mappingVersion,
                        $mapLatLngs[$index],
                        $dominantFloor,
                    );
                }
            }
        }

        return $latLngs;
    }

    /**
     * A location that is still on the facade floor after converting it to the floors behind the facade lies outside
     * every one of them, so the object it belongs to cannot be placed. Records an import warning when that happens.
     */
    private function isOutOfBounds(
        ImportStringObjects $importStringObjects,
        int                 $objectIndex,
        LatLng              $latLng,
        string              $warning,
    ): bool {
        if (!$latLng->getFloor()?->facade) {
            return false;
        }

        $this->log->parseObjectAfterConversionFloorStillOnFacade($latLng->toArrayWithFloor());

        $importStringObjects->getWarnings()->push(
            new ImportWarning(
                sprintf(__('services.mdt.io.import_string.category.object'), $objectIndex),
                $warning,
            ),
        );

        return true;
    }

    /**
     * @param array<int, mixed> $details
     * @param array<int, mixed> $vertices
     */
    private function parseObjectTriangle(
        ImportStringObjects $importStringObjects,
        MappingVersion      $mappingVersion,
        Floor               $floor,
        array               $details,
        array               $vertices,
        ?Floor              $dominantFloor = null,
    ): void {
        $weight = min(5, max(1, (int)$details[0]));

        $verticesJson = json_encode($vertices);
        if (!$this->fitsInPolyline($importStringObjects, $verticesJson)) {
            return;
        }

        $importStringObjects->getArrows()->push([
            'floor_id' => ($dominantFloor ?? $floor)->id,
            'polyline' => [
                'color'         => (!str_starts_with((string)$details[4], '#') ? '#' : '') . $details[4],
                'weight'        => $weight,
                'vertices_json' => $verticesJson,
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

    /**
     * @param array<int, mixed> $details
     * @param array<int, mixed> $vertices
     */
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

        $verticesJson = json_encode($vertices);
        if (!$this->fitsInPolyline($importStringObjects, $verticesJson)) {
            return;
        }

        $lineOrPathAttribute = [
            'floor_id' => ($dominantFloor ?? $floor)->id,
            'polyline' => [
                // Make sure there is a pound sign in front of the value at all times, but never double up should
                // MDT decide to suddenly place it here
                'color'         => (!str_starts_with((string)$details[4], '#') ? '#' : '') . $details[4],
                'weight'        => $weight,
                'vertices_json' => $verticesJson,
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

    /**
     * @param array<int, mixed>  $details
     * @param array<string, int> $spellIdsByName
     */
    private function parseObjectComment(
        ImportStringObjects $importStringObjects,
        int                 $objectIndex,
        MappingVersion      $mappingVersion,
        Floor               $floor,
        array               $details,
        bool                $assignNotesToPulls,
        array               $spellIdsByName,
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
        }

        if ($this->isOutOfBounds(
            $importStringObjects,
            $objectIndex,
            $latLng,
            __('services.mdt.io.import_string.object_out_of_bounds', ['comment' => (string)$details['4']]),
        )) {
            return;
        }

        // Try to see if we can import this comment and apply it to our pulls directly instead
        $killZoneIndex = $this->findNearestKillZoneIndex($importStringObjects, $latLng);
        if ($killZoneIndex !== null) {
            $killZoneAttribute = $importStringObjects->getKillZoneAttributes()->get($killZoneIndex);
            $newAttributes     = null;

            // A note that only lists spell names (one per line) assigns those spells to the pull instead
            $spellIds = $this->resolveSpellIdsFromComment((string)$details[4], $spellIdsByName);
            if ($spellIds !== null) {
                $assignedSpellIds = array_column($killZoneAttribute['spells'], 'spell_id');
                foreach ($spellIds as $spellId) {
                    if (!in_array($spellId, $assignedSpellIds, true)) {
                        $killZoneAttribute['spells'][] = ['spell_id' => $spellId];
                    }
                }

                $newAttributes = ['spells' => $killZoneAttribute['spells']];
            } elseif ($assignNotesToPulls && empty($killZoneAttribute['description'])) {
                // A pull holds one description - any further notes are rendered on the map as usual
                $newAttributes = ['description' => mb_substr((string)$details[4], 0, KillZone::DESCRIPTION_MAX_LENGTH)];
            }

            if ($newAttributes !== null) {
                $importStringObjects->getKillZoneAttributes()->put(
                    $killZoneIndex,
                    array_merge($killZoneAttribute, $newAttributes),
                );

                // Map icon was assigned to killzone instead - return, we're done
                return;
            }
        }

        $importStringObjects->getMapIcons()->push(array_merge([
            'mapping_version_id' => null,
            'floor_id'           => $latLng->getFloor()->id,
            'map_icon_type_id'   => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT],
            'permanent_tooltip'  => 0,
            // Bulk-inserted past MapIcon::setCommentAttribute()'s own stripping
            'comment' => mb_substr(new HtmlSanitizer()->stripAllTags((string)$details[4]), 0, MapIcon::COMMENT_MAX_LENGTH),
        ], $latLng->toArray()));
    }

    /**
     * The index of the kill zone with an enemy closest to $latLng, on the same floor and within
     * IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS, or null if there is none.
     */
    private function findNearestKillZoneIndex(ImportStringObjects $importStringObjects, LatLng $latLng): ?int
    {
        $floor           = $latLng->getFloor();
        $ingameXY        = $this->coordinatesService->calculateIngameLocationForMapLocation($latLng);
        $nearestIndex    = null;
        $nearestDistance = (float)self::IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS;

        foreach ($importStringObjects->getKillZoneAttributes() as $killZoneIndex => $killZoneAttribute) {
            foreach ($killZoneAttribute['killZoneEnemies'] as $killZoneEnemy) {
                /** @var Enemy $enemy */
                $enemy = $killZoneEnemy['enemy'];
                if ($enemy->floor_id !== $floor->id) {
                    continue;
                }

                $distance = $this->coordinatesService->distanceIngameXY(
                    $this->coordinatesService->calculateIngameLocationForMapLocation(
                        new LatLng($enemy->lat, $enemy->lng, $floor),
                    ),
                    $ingameXY,
                );

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestIndex    = $killZoneIndex;
                }
            }
        }

        return $nearestIndex;
    }

    /**
     * MDT map texts are styled with |cffRRGGBB ... |r colour runs, || for a literal pipe and {tag} icons. Tags MDT
     * does not know are drawn as written, and so are left in place here.
     */
    private function convertMapTextToPlainText(string $text): string
    {
        $text = (string)preg_replace_callback(
            '/\|\||\|c[0-9a-f]{8}|\|r/i',
            static fn(array $match): string => $match[0] === '||' ? '|' : '',
            $text,
        );

        $text = (string)preg_replace_callback('/\{([^{}]+)\}/', function (array $match): string {
            [$name, $argument] = array_pad(explode(':', $match[1], 2), 2, '');
            $name              = mb_strtolower($name);

            if ($name === 'spell' && preg_match('/^(\d+)/', $argument, $spellIdMatch)) {
                return $this->getMapTextSpellName((int)$spellIdMatch[1]);
            }

            if (($name === 'icon' || $name === 'spec') && $argument !== '' && !str_contains($argument, '|')) {
                return '';
            }

            if ($argument !== '') {
                return $match[0];
            }

            $raidMarkerIndex = preg_match('/^rt([1-8])$/', $name, $raidMarkerMatch) ? (int)$raidMarkerMatch[1] :
                (array_search($name, self::MDT_MAP_TEXT_RAID_MARKERS, true) ?: ($name === 'x' ? 7 : null));
            if ($raidMarkerIndex !== null) {
                return __(sprintf('mapicontypes.raid_marker_%s', self::MDT_MAP_TEXT_RAID_MARKERS[$raidMarkerIndex]));
            }

            return in_array($name, self::MDT_MAP_TEXT_WORD_TAGS, true) ? $match[1] : $match[0];
        }, $text);

        $lines = array_map(
            static fn(string $line): string => trim((string)preg_replace('/[ \t]{2,}/', ' ', $line)),
            preg_split('/\R/u', $text) ?: [],
        );

        return trim(implode("\n", $lines));
    }

    /**
     * The name of a spell in a map text's {spell:ID} tag, or an empty string for a spell we do not know.
     */
    private function getMapTextSpellName(int $spellId): string
    {
        $spell = Spell::query()->find($spellId, ['id', 'name']);
        if ($spell === null) {
            return '';
        }

        $translatedName = __($spell->name);

        // An untranslated name comes back as its translation key
        return is_string($translatedName) && $translatedName !== $spell->name ? $translatedName : '';
    }

    /**
     * @param  array<string, int>   $spellIdsByName
     * @return array<int, int>|null The IDs of the spells if every non-empty line of the comment is a spell name, null otherwise.
     */
    private function resolveSpellIdsFromComment(string $comment, array $spellIdsByName): ?array
    {
        $spellIds = [];

        foreach (preg_split('/\R/u', $comment) ?: [] as $line) {
            $line = mb_strtolower(trim($line));
            if ($line === '') {
                continue;
            }

            if (!isset($spellIdsByName[$line])) {
                return null;
            }

            $spellIds[] = $spellIdsByName[$line];
        }

        return $spellIds === [] ? null : array_values(array_unique($spellIds));
    }

    /**
     * Every spell that can be assigned to a pull, by its lower-cased name in both en_US and the current locale -
     * the MDT export writes a pull's spells in the exporting user's locale.
     *
     * @return array<string, int>
     */
    private function getSpellIdsByName(): array
    {
        $result = self::SPELL_IDS_BY_ALIAS;

        $spells = Spell::query()
            ->where('selectable', true)
            ->orWhereIn('id', KnownSpell::BLOODLUSTY_SPELLS)
            ->orderBy('id')
            ->get(['id', 'name'])
            // Bloodlust effects claim a name shared with any other spell
            ->sortBy(static fn(Spell $spell): int => in_array($spell->id, KnownSpell::BLOODLUSTY_SPELLS, true) ? 0 : 1);

        foreach ($spells as $spell) {
            foreach ([__($spell->name, [], 'en_US'), __($spell->name)] as $translatedName) {
                // An untranslated name comes back as its translation key
                if (!is_string($translatedName) || $translatedName === $spell->name) {
                    continue;
                }

                $result[mb_strtolower(trim($translatedName))] ??= $spell->id;
            }
        }

        return $result;
    }

    /**
     * Wrapped in a retried transaction - concurrent imports bulk-inserting into the shared
     * `polylines` table can hit a MySQL lock wait timeout under contention (#4239).
     */
    public function applyObjectsToDungeonRoute(
        ImportStringObjects $importStringObjects,
        DungeonRoute        $dungeonRoute,
    ): void {
        DB::transaction(fn() => $this->doApplyObjectsToDungeonRoute($importStringObjects, $dungeonRoute), 3);
    }

    private function doApplyObjectsToDungeonRoute(
        ImportStringObjects $importStringObjects,
        DungeonRoute        $dungeonRoute,
    ): void {
        $now = now();

        /**
         * Each entry maps an ImportStringObjects collection to its model class and DungeonRoute relation name.
         * This unified loop replaces the previous copy-pasted brushline/path blocks.
         *
         * @var Collection<int, array{objects: Collection<int, mixed>, model: class-string, relation: string}> $typedObjects
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

    /**
     * A line drawn with more points than a polyline can store is left out with a warning, rather than failing the import.
     */
    private function fitsInPolyline(ImportStringObjects $importStringObjects, string $verticesJson): bool
    {
        if (strlen($verticesJson) <= Polyline::VERTICES_JSON_MAX_LENGTH) {
            return true;
        }

        $importStringObjects->getWarnings()->push(
            new ImportWarning(
                __('services.mdt.io.import_string.category.lines'),
                __('services.mdt.io.import_string.line_too_long'),
            ),
        );

        return false;
    }
}
