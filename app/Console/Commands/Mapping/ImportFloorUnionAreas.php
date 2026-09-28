<?php

namespace App\Console\Commands\Mapping;

use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Replaces the FloorUnionAreas of a mapping version's facade from a JSON file of zone outlines.
 *
 * The file looks like this; each floor is matched by `ui_map_id` or by `floor_index`:
 *
 * ```json
 * {
 *     "coordinate_space": "ui",
 *     "floors": [
 *         {"ui_map_id": 1413, "polygons": [[{"x": 0.51, "y": 0.43}, {"x": 0.55, "y": 0.47}, {"x": 0.49, "y": 0.5}]]},
 *         {"floor_index": 3, "polygons": [...]}
 *     ]
 * }
 * ```
 *
 * `ui`: x/y run 0..1 over the facade floor, from the top left. `ingame`: x/y are world coordinates on the matched
 * floor, converted onto the facade through that floor's FloorUnion.
 */
class ImportFloorUnionAreas extends Command
{
    public const string COORDINATE_SPACE_UI     = 'ui';
    public const string COORDINATE_SPACE_INGAME = 'ingame';

    public const array COORDINATE_SPACE_ALL = [
        self::COORDINATE_SPACE_UI,
        self::COORDINATE_SPACE_INGAME,
    ];

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mapping:importfloorunionareas
                            {mappingVersion : The id of the mapping version whose facade receives the areas}
                            {file : Path to the JSON file with the outlines per floor}
                            {--no-save : Do not run mapping:save afterwards}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Replaces the FloorUnionAreas of the matched floors\' FloorUnions with outlines from a JSON file.';

    public function handle(CoordinatesServiceInterface $coordinatesService): int
    {
        $mappingVersion = MappingVersion::query()->with('dungeon.floors')->find($this->argument('mappingVersion'));
        if ($mappingVersion === null) {
            $this->error(sprintf('Mapping version %s not found', $this->argument('mappingVersion')));

            return self::FAILURE;
        }

        $data = $this->readFile((string)$this->argument('file'));
        if ($data === null) {
            return self::FAILURE;
        }

        /** @var Collection<int, Floor> $floors */
        $floors = $mappingVersion->dungeon->floors->where('facade', false);
        /** @var Collection<int, FloorUnion> $floorUnions */
        $floorUnions = $mappingVersion->floorUnions()->with(['floor', 'targetFloor'])->get();

        $imported = 0;
        foreach ($data['floors'] as $floorData) {
            $floor = isset($floorData['ui_map_id'])
                ? $floors->firstWhere('ui_map_id', (int)$floorData['ui_map_id'])
                : $floors->firstWhere('index', (int)$floorData['floor_index']);
            $floorLabel = isset($floorData['ui_map_id'])
                ? sprintf('ui_map_id %d', $floorData['ui_map_id'])
                : sprintf('floor_index %d', $floorData['floor_index']);

            if ($floor === null) {
                $this->warn(sprintf('- Skipped %s: no such floor in %s', $floorLabel, $mappingVersion->dungeon->key));

                continue;
            }

            $floorUnionsOfFloor = $floorUnions->where('target_floor_id', $floor->id);
            if ($floorUnionsOfFloor->count() !== 1) {
                $this->warn(sprintf(
                    '- Skipped %s (floor %d): expected one FloorUnion targeting it, found %d',
                    $floorLabel,
                    $floor->id,
                    $floorUnionsOfFloor->count(),
                ));

                continue;
            }

            /** @var FloorUnion $floorUnion */
            $floorUnion = $floorUnionsOfFloor->first();

            $verticesPerPolygon = array_map(
                fn(array $polygon) => $this->convertPolygon(
                    $coordinatesService,
                    $mappingVersion,
                    $floorUnion,
                    $data['coordinate_space'],
                    $polygon,
                ),
                $floorData['polygons'],
            );

            DB::transaction(static function () use ($mappingVersion, $floorUnion, $verticesPerPolygon) {
                FloorUnionArea::query()->where('floor_union_id', $floorUnion->id)->delete();

                foreach ($verticesPerPolygon as $vertices) {
                    FloorUnionArea::create([
                        'mapping_version_id' => $mappingVersion->id,
                        'floor_id'           => $floorUnion->floor_id,
                        'floor_union_id'     => $floorUnion->id,
                        'vertices_json'      => json_encode($vertices),
                    ]);
                }
            });

            $imported++;
            $this->info(sprintf(
                '- Imported %d area(s) for %s (floor %d, FloorUnion %d)',
                count($verticesPerPolygon),
                $floorLabel,
                $floor->id,
                $floorUnion->id,
            ));
        }

        $this->info(sprintf('Imported areas for %d of %d floor(s)', $imported, count($data['floors'])));

        if (!$this->option('no-save')) {
            $this->call('mapping:save');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{coordinate_space: string, floors: array<int, array{ui_map_id?: int, floor_index?: int, polygons: array<int, array<int, array{x: float, y: float}>>}>}|null
     */
    private function readFile(string $filePath): ?array
    {
        if (!is_file($filePath)) {
            $this->error(sprintf('File %s not found', $filePath));

            return null;
        }

        $data = json_decode((string)file_get_contents($filePath), true);
        if (!is_array($data)) {
            $this->error(sprintf('File %s does not contain a JSON object', $filePath));

            return null;
        }

        $validator = Validator::make($data, [
            'coordinate_space'        => ['required', Rule::in(self::COORDINATE_SPACE_ALL)],
            'floors'                  => ['required', 'array', 'min:1'],
            'floors.*.ui_map_id'      => ['required_without:floors.*.floor_index', 'integer'],
            'floors.*.floor_index'    => ['required_without:floors.*.ui_map_id', 'integer'],
            'floors.*.polygons'       => ['required', 'array', 'min:1'],
            'floors.*.polygons.*'     => ['required', 'array', 'min:3'],
            'floors.*.polygons.*.*.x' => ['required', 'numeric'],
            'floors.*.polygons.*.*.y' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return null;
        }

        return $data;
    }

    /**
     * @param array<int, array{x: float, y: float}> $polygon
     *
     * @return array<int, array{lat: float, lng: float}>
     */
    private function convertPolygon(
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        FloorUnion                  $floorUnion,
        string                      $coordinateSpace,
        array                       $polygon,
    ): array {
        return array_map(static function (array $vertex) use (
            $coordinatesService,
            $mappingVersion,
            $floorUnion,
            $coordinateSpace
        ) {
            if ($coordinateSpace === self::COORDINATE_SPACE_UI) {
                $latLng = new LatLng(
                    $vertex['y'] * CoordinatesService::MAP_MAX_LAT,
                    $vertex['x'] * CoordinatesService::MAP_MAX_LNG,
                    $floorUnion->floor,
                );
            } else {
                $latLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
                    $mappingVersion,
                    $coordinatesService->calculateMapLocationForIngameLocation(
                        new IngameXY($vertex['x'], $vertex['y'], $floorUnion->targetFloor),
                    ),
                    $floorUnion,
                );
            }

            return [
                'lat' => round($latLng->getLat(), 4),
                'lng' => round($latLng->getLng(), 4),
            ];
        }, $polygon);
    }
}
