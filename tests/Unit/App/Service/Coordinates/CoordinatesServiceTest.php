<?php

namespace Tests\Unit\App\Service\Coordinates;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Data\MDTDungeon;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
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
     * Dungeons whose enemy positions deviate a lot from MDT's but are valid, manually checked.
     *
     * King's Rest earns its place differently from the rest: it had a complete hand-made mapping
     * before MDT 6.2 repointed it to a zoomed facade, and the import deliberately keeps a mapper's
     * position over MDT's whenever the two are within 150 in-game units. Those kept positions were
     * placed in the non-facade frame the mapper worked in, so measuring them in MDT's new frame
     * surfaces drift that comparing within a single frame never showed - 31 of its 101 enemies
     * exceed the margin, median 17.5. The conversion is not what deviates: the 30 enemies whose
     * positions do come from MDT 6.2 round-trip back to within 0.07 (#3734).
     *
     * The Blinding Vale earns its place the same way King's Rest does: MDT 6.2.3's changelog notes
     * it "Corrected enemy positions and icon sizes throughout The Blinding Vale", but the import's
     * >150-in-game-unit position-preservation rule (see the "we ignore MDT's position" comment in
     * MDTMappingImportService::importEnemies()) kept the pre-correction positions unchanged since
     * MDT's nudges were all under that threshold - so this dungeon's stored positions never adopted
     * MDT's correction at all. Wotuu confirmed the kept (pre-correction) positions are still correct
     * in-game (#4108).
     *
     * @var array<int, string>
     */
    private const array DEVIATES_FROM_MDT_DUNGEON_KEYS = [
        DungeonKey::EYE_OF_AZSHARA->value,
        DungeonKey::VAULT_OF_THE_WARDENS->value,
        DungeonKey::WINDRUNNER_SPIRE->value,
        DungeonKey::NEXUS_POINT_XENAS->value,
        DungeonKey::THE_ROOKERY->value,
        DungeonKey::KINGS_REST->value,
        DungeonKey::THE_BLINDING_VALE->value,
    ];

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

    /**
     * @return array<int, mixed>
     */
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

        // Every dungeon this test can say anything about: present in MDT, and not one of the
        // manually-checked dungeons that deviate a lot from the MDT data while still being valid.
        $checkableDungeonIds = Dungeon::query()
            ->whereNotIn('key', self::DEVIATES_FROM_MDT_DUNGEON_KEYS)
            ->get()
            ->filter(static fn(Dungeon $dungeon) => Conversion::hasMDTDungeonName($dungeon->key))
            ->pluck('id');

        // Select the mapping version with the highest version number, unique by game_version_id.
        // The uncheckable dungeons are filtered here rather than only in the loop below on purpose:
        // exactly one mapping version per game version is examined, so a dungeon that gets skipped
        // owning the newest one leaves this test asserting nothing at all. That is how it silently
        // stopped covering anything once King's Rest was allowlisted, and it is a live scenario -
        // a dungeon is unmapped in Conversion right up until it is added to DUNGEON_NAME_MAPPING,
        // which is exactly when a new season's mapping is being imported (#3734).
        $mappingVersions = MappingVersion::with(['enemies', 'dungeon'])
            ->whereIn('id', function ($query) use ($checkableDungeonIds) {
                $query->selectRaw('MAX(id)')
                    ->from('mapping_versions')
//                    ->where('id', 606) // temp
                    ->whereIn('dungeon_id', $checkableDungeonIds)
                    ->groupBy('game_version_id');
            })
            ->get();

        $margin      = 20;
        $comparisons = 0;

        foreach ($mappingVersions as $mappingVersion) {
            // Skip dungeons not available in MDT
            if (Conversion::hasMDTDungeonName($mappingVersion->dungeon->key) === false) {
                continue;
            }

            // MDT 6.2 deleted its MistsOfPandaria folder, so there is no MoP mapping left to compare
            // against. Conversion is keyed by dungeon, not by game version, so a dungeon that exists in
            // both MoP Classic and retail (Temple of the Jade Serpent) now resolves to its retail lua -
            // correct for the retail mapping version, meaningless for the MoP one.
            if ($mappingVersion->gameVersion->key === GameVersion::GAME_VERSION_MOP) {
                continue;
            }

            /** @var MappingVersion $mappingVersion */
            $enemies = $mappingVersion->enemies()->with('floor')->get();

            $mdtNpcs = new MDTDungeon(
                ServiceFixtures::getCacheServiceMock($this),
                $coordinatesService,
                $mappingVersion->dungeon,
            )->getMDTNPCs();

            /** @var Collection<string, array{x: float, y: float}> $result */
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
                            "X coordinate for NPC ID $npcId (index $index) is too low [MDT XY: [$cloneX, $cloneY], MappingVersion: $mvId, Dungeon: $dungeonName]",
                        );
                        $this->assertLessThan(
                            $clone['x'] + $margin,
                            $converted['x'],
                            "X coordinate for NPC ID $npcId (index $index) is too high [MDT XY: [$cloneX, $cloneY], MappingVersion: $mvId, Dungeon: $dungeonName]",
                        );

                        $this->assertGreaterThan(
                            $clone['y'] - $margin,
                            $converted['y'],
                            "Y coordinate for NPC ID $npcId (index $index) is too low [MDT XY: [$cloneX, $cloneY], MappingVersion: $mvId, Dungeon: $dungeonName]",
                        );
                        $this->assertLessThan(
                            $clone['y'] + $margin,
                            $converted['y'],
                            "Y coordinate for NPC ID $npcId (index $index) is too high [MDT XY: [$cloneX, $cloneY], MappingVersion: $mvId, Dungeon: $dungeonName]",
                        );

                        $comparisons++;

                        break;
                    }
                }
            }
        }

        // Without this the test reports as passing when every mapping version it selected was
        // skipped, having asserted nothing at all - which is exactly how it silently stopped
        // covering anything once a skipped dungeon owned the newest mapping version (#3734).
        $this->assertGreaterThan(
            0,
            $comparisons,
            'No enemy was compared against MDT at all - this test covered nothing',
        );
    }
}
