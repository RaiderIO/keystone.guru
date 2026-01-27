<?php

namespace Tests\Unit\App\Service\Coordinates;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Service\Coordinates\CoordinatesService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\ServiceFixtures;
use Tests\TestCases\PublicTestCase;

final class CoordinatesServiceTest extends PublicTestCase
{
    /**
     * Scenario: Tests that the ingame location is converted correctly from the given map location.
     */
    #[Test]
    #[DataProvider('checkCalculateIngameLocationForMapLocation_GivenLatLng_ShouldReturn_Provider')]
    #[Group('CoordinatesService')]
    public function checkCalculateIngameLocationForMapLocation_GivenLatLng_ShouldReturn(
        LatLng   $latLng,
        IngameXY $expected,
    ): void {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Act
        $result = $coordinatesService->calculateIngameLocationForMapLocation($latLng);

        // Assert
        $this->assertEquals($expected->getX(), $result->getX());
        $this->assertEquals($expected->getY(), $result->getY());
    }

    public static function checkCalculateIngameLocationForMapLocation_GivenLatLng_ShouldReturn_Provider(): array
    {
        return [
            [
                new LatLng(
                    CoordinatesService::MAP_MAX_LAT / 2,
                    CoordinatesService::MAP_MAX_LNG / 2,
                    new Floor([
                        'ingame_min_x' => 0,
                        'ingame_max_x' => 100,
                        'ingame_min_y' => 0,
                        'ingame_max_y' => 100,
                    ]),
                ),
                new IngameXY(50, 50),
            ],
            [
                new LatLng(
                    CoordinatesService::MAP_MAX_LAT / 2,
                    CoordinatesService::MAP_MAX_LNG / 2,
                    new Floor([
                        'ingame_min_x' => 100,
                        'ingame_max_x' => 1000,
                        'ingame_min_y' => 100,
                        'ingame_max_y' => 1000,
                    ]),
                ),
                new IngameXY(550, 550),
            ],
            [
                new LatLng(
                    CoordinatesService::MAP_MAX_LAT / 4,
                    CoordinatesService::MAP_MAX_LNG / 4,
                    new Floor([
                        'ingame_min_x' => 100,
                        'ingame_max_x' => 1000,
                        'ingame_min_y' => 100,
                        'ingame_max_y' => 1000,
                    ]),
                ),
                new IngameXY(775, 775),
            ],
            [
                new LatLng(
                    CoordinatesService::MAP_MAX_LAT / 10,
                    CoordinatesService::MAP_MAX_LNG / 4,
                    new Floor([
                        'ingame_min_x' => 100,
                        'ingame_max_x' => 1000,
                        'ingame_min_y' => 50,
                        'ingame_max_y' => 100,
                    ]),
                ),
                new IngameXY(775, 95),
            ],
        ];
    }

    /**
     * Scenario: Tests that mapping versions accuracy vs MDT isn't totally out of whack
     * @throws \Exception
     */
    #[Test]
    #[Group('UsesLua')]
    #[Group('CoordinatesService')]
    public function checkConvertMapLocationToFacadeMapLocationAccuracy_GivenMappingVersion_ShouldBeWithinMargin(): void
    {
        // Arrange
        $coordinatesService = ServiceFixtures::getCoordinatesServiceMock($this);

        // Select the mapping version with the highest version number, unique by game_version_id
        $mappingVersions = MappingVersion::with(['enemies', 'dungeon'])
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('mapping_versions')
//                    ->where('id', 606) // temp
                    ->groupBy('game_version_id');
            })
            ->get();

        $margin = 20;

        foreach ($mappingVersions as $mappingVersion) {
            // Skip dungeons not available in MDT
            if (Conversion::hasMDTDungeonName($mappingVersion->dungeon->key) === false) {
                continue;
            }

            // Manually checked - they deviate a lot from the MDT data but they're valid
            if (in_array($mappingVersion->dungeon->key, [
                Dungeon::DUNGEON_EYE_OF_AZSHARA,
                Dungeon::DUNGEON_VAULT_OF_THE_WARDENS,
                Dungeon::DUNGEON_WINDRUNNER_SPIRE,
            ])) {
                continue;
            }

            /** @var MappingVersion $mappingVersion */
            $enemies = $mappingVersion->enemies()->with('floor')->get();

            $mdtNpcs = new MDTDungeon(
                ServiceFixtures::getCacheServiceMock($this),
                $coordinatesService,
                $mappingVersion->dungeon,
            )->getMDTNPCs();

            /** @var Collection<array{x: float, y: float}> $result */
            $result = collect();
            foreach ($enemies as $enemy) {
                $result->put(
                    $enemy->getUniqueKey(),
                    Conversion::convertLatLngToMDTCoordinate(
                        $coordinatesService->convertMapLocationToFacadeMapLocation(
                            $mappingVersion,
                            $enemy->getLatLng(),
                        ),
                    ),
                );
            }

            // Assert
            foreach ($result as $uniqueKey => $converted) {
                [$npcId, $index] = explode('-', (string)$uniqueKey);

                foreach ($mdtNpcs as $mdtNpc) {
                    if ($mdtNpc->getId() === (int)$npcId) {
                        $clone  = $mdtNpc->getClones()[$index];
                        $cloneX = $clone['x'];
                        $cloneY = $clone['y'];

                        $mvId        = $mappingVersion->id;
                        $dungeonName = __($mappingVersion->dungeon->name);

                        $this->assertGreaterThan(
                            $clone['x'] - $margin,
                            $converted['x'],
                            "X coordinate for NPC ID $npcId (index $index) is too low [XY: [$cloneX, $cloneY], MappingVersion: $mvId, Dungeon: $dungeonName]",
                        );
                        $this->assertLessThan(
                            $clone['x'] + $margin,
                            $converted['x'],
                            "X coordinate for NPC ID $npcId (index $index) is too high [XY: [$cloneX, $cloneY], MappingVersion: $mvId, Dungeon: $dungeonName]",
                        );

                        $this->assertGreaterThan(
                            $clone['y'] - $margin,
                            $converted['y'],
                            "Y coordinate for NPC ID $npcId (index $index) is too low [XY: [$cloneX, $cloneY], MappingVersion: $mvId, Dungeon: $dungeonName]",
                        );
                        $this->assertLessThan(
                            $clone['y'] + $margin,
                            $converted['y'],
                            "Y coordinate for NPC ID $npcId (index $index) is too high [XY: [$cloneX, $cloneY], MappingVersion: $mvId, Dungeon: $dungeonName]",
                        );

                        break;
                    }
                }
            }
        }
    }
}
