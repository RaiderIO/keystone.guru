<?php

namespace Tests\Feature\Console\Commands\Mapping;

use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Floor\FloorUnion;
use App\Models\Floor\FloorUnionArea;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Mapping')]
#[Group('FloorUnion')]
final class ImportFloorUnionAreasTest extends PublicTestCase
{
    private const string DUNGEON_KEY = 'siege_of_niu_zao_temple';

    private ?MappingVersion $mappingVersion = null;

    private Floor $facadeFloor;

    private Floor $firstFloor;

    private Floor $secondFloor;

    private FloorUnion $firstFloorUnion;

    private FloorUnion $secondFloorUnion;

    /** @var list<string> */
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();

        /** @var Dungeon $dungeon */
        $dungeon               = Dungeon::query()->where('key', self::DUNGEON_KEY)->firstOrFail();
        $currentMappingVersion = $dungeon->getCurrentMappingVersion();

        $this->mappingVersion = MappingVersion::create([
            'game_version_id'                 => $currentMappingVersion->game_version_id,
            'dungeon_id'                      => $dungeon->id,
            'version'                         => $currentMappingVersion->version + 1,
            'enemy_forces_required'           => $currentMappingVersion->enemy_forces_required,
            'enemy_forces_required_teeming'   => $currentMappingVersion->enemy_forces_required_teeming,
            'enemy_forces_shrouded'           => $currentMappingVersion->enemy_forces_shrouded,
            'enemy_forces_shrouded_zul_gamux' => $currentMappingVersion->enemy_forces_shrouded_zul_gamux,
            'timer_max_seconds'               => $currentMappingVersion->timer_max_seconds,
            'facade_enabled'                  => true,
        ]);
        // Creating a mapping version clones the previous one's unions; the tests place their own.
        $this->mappingVersion->floorUnionAreas()->delete();
        $this->mappingVersion->floorUnions()->delete();

        $floors                                 = $dungeon->floors()->orderBy('index')->get();
        $this->facadeFloor                      = $floors->firstWhere('facade', true);
        [$this->firstFloor, $this->secondFloor] = $floors->where('facade', false)->values()->all();

        $this->firstFloorUnion  = $this->createFloorUnion($this->firstFloor, -100, 100, 50, 0);
        $this->secondFloorUnion = $this->createFloorUnion($this->secondFloor, -150, 250, 80, 30);
    }

    protected function tearDown(): void
    {
        try {
            $this->mappingVersion?->delete();
            foreach ($this->files as $file) {
                @unlink($file);
            }
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenUiSpaceOutlineMatchedByUiMapId_replacesTheUnionsAreasWithFacadeVertices(): void
    {
        // Arrange
        $existingArea = $this->createFloorUnionArea($this->firstFloorUnion);
        $file         = $this->writeFile([
            'coordinate_space' => 'ui',
            'floors'           => [
                [
                    'ui_map_id' => $this->firstFloor->ui_map_id,
                    'polygons'  => [
                        [['x' => 0.5, 'y' => 0.25], ['x' => 0.75, 'y' => 0.25], ['x' => 0.75, 'y' => 0.5]],
                        [['x' => 0.1, 'y' => 0.1], ['x' => 0.2, 'y' => 0.1], ['x' => 0.2, 'y' => 0.2]],
                    ],
                ],
            ],
        ]);

        // Act
        $exitCode = $this->runImport($file);

        // Assert
        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertNull(FloorUnionArea::query()->find($existingArea->id));

        $areas = FloorUnionArea::query()->where('floor_union_id', $this->firstFloorUnion->id)->orderBy('id')->get();
        $this->assertCount(2, $areas);
        $this->assertSame($this->facadeFloor->id, $areas[0]->floor_id);
        $this->assertSame($this->mappingVersion->id, $areas[0]->mapping_version_id);
        $this->assertSame([
            ['lat' => -64, 'lng' => 192],
            ['lat' => -64, 'lng' => 288],
            ['lat' => -128, 'lng' => 288],
        ], json_decode($areas[0]->vertices_json, true));
    }

    #[Test]
    public function handle_givenIngameSpaceOutlineMatchedByFloorIndex_convertsThroughTheFloorsUnion(): void
    {
        // Arrange
        $coordinatesService = app(CoordinatesServiceInterface::class);
        $floor              = $this->secondFloor;
        $ingameVertices     = [
            [$floor->ingame_min_x, $floor->ingame_min_y],
            [$floor->ingame_max_x, $floor->ingame_min_y],
            [($floor->ingame_min_x + $floor->ingame_max_x) / 2, $floor->ingame_max_y],
        ];
        $file = $this->writeFile([
            'coordinate_space' => 'ingame',
            'floors'           => [
                [
                    'floor_index' => $floor->index,
                    'polygons'    => [array_map(static fn(array $xy) => ['x' => $xy[0], 'y' => $xy[1]], $ingameVertices)],
                ],
            ],
        ]);

        // Act
        $exitCode = $this->runImport($file);

        // Assert
        $this->assertSame(0, $exitCode, Artisan::output());

        /** @var FloorUnionArea $area */
        $area = FloorUnionArea::query()->where('floor_union_id', $this->secondFloorUnion->id)->sole();
        foreach (json_decode($area->vertices_json, true) as $i => $vertex) {
            $ingameXY = $coordinatesService->calculateIngameLocationForMapLocation(
                $coordinatesService->convertFacadeMapLocationToMapLocation(
                    $this->mappingVersion,
                    new LatLng($vertex['lat'], $vertex['lng'], $this->facadeFloor),
                    $floor,
                ),
            );
            $this->assertEqualsWithDelta($ingameVertices[$i][0], $ingameXY->getX(), 0.5);
            $this->assertEqualsWithDelta($ingameVertices[$i][1], $ingameXY->getY(), 0.5);
        }
    }

    #[Test]
    public function handle_givenFloorWithoutUnionOrUnknownFloor_skipsItAndImportsTheRest(): void
    {
        // Arrange
        $this->secondFloorUnion->delete();
        $polygon = [['x' => 0.5, 'y' => 0.5], ['x' => 0.6, 'y' => 0.5], ['x' => 0.6, 'y' => 0.6]];
        $file    = $this->writeFile([
            'coordinate_space' => 'ui',
            'floors'           => [
                ['ui_map_id' => 987654321, 'polygons' => [$polygon]],
                ['floor_index' => $this->secondFloor->index, 'polygons' => [$polygon]],
                ['floor_index' => $this->firstFloor->index, 'polygons' => [$polygon]],
            ],
        ]);

        // Act
        $exitCode = $this->runImport($file);
        $output   = Artisan::output();

        // Assert
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Skipped ui_map_id 987654321: no such floor', $output);
        $this->assertStringContainsString(
            sprintf('Skipped floor_index %d (floor %d): expected one FloorUnion targeting it, found 0', $this->secondFloor->index, $this->secondFloor->id),
            $output,
        );
        $this->assertStringContainsString('Imported areas for 1 of 3 floor(s)', $output);
        $this->assertSame(1, FloorUnionArea::query()->where('mapping_version_id', $this->mappingVersion->id)->count());
    }

    #[Test]
    public function handle_givenInvalidFile_failsWithoutTouchingExistingAreas(): void
    {
        // Arrange
        $existingArea = $this->createFloorUnionArea($this->firstFloorUnion);
        $file         = $this->writeFile([
            'coordinate_space' => 'pixels',
            'floors'           => [
                [
                    'ui_map_id' => $this->firstFloor->ui_map_id,
                    'polygons'  => [[['x' => 0.5, 'y' => 0.25], ['x' => 0.75, 'y' => 0.25]]],
                ],
            ],
        ]);

        // Act
        $exitCode = $this->runImport($file);
        $output   = Artisan::output();

        // Assert
        $this->assertSame(1, $exitCode, $output);
        $this->assertStringContainsString('coordinate space', $output);
        $this->assertStringContainsString('floors.0.polygons.0', $output);
        $this->assertNotNull(FloorUnionArea::query()->find($existingArea->id));
    }

    #[Test]
    public function handle_givenMissingFile_fails(): void
    {
        // Act
        $exitCode = $this->runImport('/tmp/does-not-exist-floor-union-areas.json');

        // Assert
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('not found', Artisan::output());
    }

    #[Test]
    public function handle_givenUnknownMappingVersion_fails(): void
    {
        // Arrange
        $file = $this->writeFile(['coordinate_space' => 'ui', 'floors' => []]);

        // Act
        $exitCode = Artisan::call('mapping:importfloorunionareas', [
            'mappingVersion' => 999999999,
            'file'           => $file,
            '--no-save'      => true,
        ]);

        // Assert
        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Mapping version 999999999 not found', Artisan::output());
    }

    private function runImport(string $file): int
    {
        return Artisan::call('mapping:importfloorunionareas', [
            'mappingVersion' => $this->mappingVersion->id,
            'file'           => $file,
            '--no-save'      => true,
        ]);
    }

    private function createFloorUnion(Floor $targetFloor, float $lat, float $lng, float $size, float $rotation): FloorUnion
    {
        return FloorUnion::create([
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $this->facadeFloor->id,
            'target_floor_id'    => $targetFloor->id,
            'lat'                => $lat,
            'lng'                => $lng,
            'size'               => $size,
            'rotation'           => $rotation,
        ]);
    }

    private function createFloorUnionArea(FloorUnion $floorUnion): FloorUnionArea
    {
        return FloorUnionArea::create([
            'mapping_version_id' => $this->mappingVersion->id,
            'floor_id'           => $this->facadeFloor->id,
            'floor_union_id'     => $floorUnion->id,
            'vertices_json'      => json_encode([['lat' => -1, 'lng' => 1], ['lat' => -1, 'lng' => 2], ['lat' => -2, 'lng' => 2]]),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeFile(array $data): string
    {
        $file = tempnam(sys_get_temp_dir(), 'floor_union_areas_');
        file_put_contents($file, json_encode($data));
        $this->files[] = $file;

        return $file;
    }
}
