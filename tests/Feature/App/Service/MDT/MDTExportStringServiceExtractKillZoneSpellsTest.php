<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use App\Models\Spell\Spell;
use App\Service\Coordinates\CoordinatesService;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\MDTExportStringServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('UsesLua')]
#[Group('MDTExportStringService')]
#[Group('MDTExportStringServiceExtractKillZoneSpells')]
final class MDTExportStringServiceExtractKillZoneSpellsTest extends MDTExportStringServiceTestBase
{
    /** @var float The importer attaches a note to a pull when one of the pull's enemies is closer than this */
    private const float IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS = 50;

    #[Test]
    public function extractObjects_givenKillZoneWithBloodlust_exportsNoteWithSpellNameBelowTheEnemy(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies(enemyFilter: $this->isNotAtTheBottomOfTheMap(...));
            $enemy        = $this->getSafeMdtEnemies($dungeonRoute, enemyFilter: $this->isNotAtTheBottomOfTheMap(...))->first();
            $this->createKillZone($dungeonRoute, 1, [Spell::SPELL_BLOODLUST], $enemy);

            $warnings = collect();

            // Act
            $objects = $this->exportObjects($dungeonRoute, $warnings);

            // Assert
            $this->assertEmpty($warnings);
            $this->assertCount(1, $objects);
            $this->assertSame('Bloodlust', $objects[0]['d'][4]);
            $this->assertNoteIsBelowEnemy($objects[0], $enemy);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function extractObjects_givenKillZoneWithSeveralSpells_exportsOneNoteListingEverySpellInAssignmentOrder(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $enemy        = $this->getSafeMdtEnemies($dungeonRoute)->first();
            $this->createKillZone($dungeonRoute, 1, [Spell::SPELL_TIME_WARP, Spell::SPELL_BLOODLUST], $enemy);

            // Act
            $objects = $this->exportObjects($dungeonRoute);

            // Assert
            $this->assertCount(1, $objects);
            $this->assertSame("Time Warp\nBloodlust", $objects[0]['d'][4]);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function extractObjects_givenKillZoneWithoutSpells_exportsNoNote(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $enemy        = $this->getSafeMdtEnemies($dungeonRoute)->first();
            $this->createKillZone($dungeonRoute, 1, [], $enemy);

            // Act
            $objects = $this->exportObjects($dungeonRoute);

            // Assert
            $this->assertEmpty($objects);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function extractObjects_givenSeveralKillZonesOfWhichSomeHaveSpells_exportsOneNotePerKillZoneWithSpells(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies(enemyCount: 3);
            $enemies      = $this->getSafeMdtEnemies($dungeonRoute, limit: 3);
            $this->createKillZone($dungeonRoute, 1, [Spell::SPELL_HEROISM], $enemies->get(0));
            $this->createKillZone($dungeonRoute, 2, [], $enemies->get(1));
            $this->createKillZone($dungeonRoute, 3, [Spell::SPELL_BLOODLUST], $enemies->get(2));

            // Act
            $objects = $this->exportObjects($dungeonRoute);

            // Assert
            $this->assertSame(['Heroism', 'Bloodlust'], array_column(array_column($objects, 'd'), 4));
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function extractObjects_givenKillZoneWithSpellsAndDescription_exportsSpellsNoteBelowDescriptionNote(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies(enemyFilter: $this->isNotAtTheBottomOfTheMap(...));
            $enemy        = $this->getSafeMdtEnemies($dungeonRoute, enemyFilter: $this->isNotAtTheBottomOfTheMap(...))->first();
            $this->createKillZone($dungeonRoute, 1, [Spell::SPELL_BLOODLUST], $enemy, ['description' => 'Pull the pack']);

            // Act
            $objects = $this->exportObjects($dungeonRoute);

            // Assert
            $this->assertCount(2, $objects);
            [$descriptionNote, $spellsNote] = $objects;
            $this->assertSame('Pull the pack', $descriptionNote['d'][4]);
            $this->assertSame('Bloodlust', $spellsNote['d'][4]);
            $this->assertLessThan((float)$descriptionNote['d'][1], (float)$spellsNote['d'][1]);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function extractObjects_givenKillZoneWithSpellsWithoutEnemiesOrKillArea_returnsWarningAndNoNote(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleNonFacadeDungeonRoute();
            $this->createKillZone($dungeonRoute, 4, [Spell::SPELL_BLOODLUST]);

            $warnings = collect();

            // Act
            $objects = $this->exportObjects($dungeonRoute, $warnings);

            // Assert
            $this->assertEmpty($objects);
            $this->assertCount(1, $warnings);
            /** @var ImportWarning $warning */
            $warning = $warnings->first();
            $this->assertSame('Pull 4', $warning->getCategory());
            $this->assertSame(__('services.mdt.io.export_string.unable_to_place_kill_zone_spells_note'), $warning->getMessage());
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function extractObjects_givenKillZoneWithSpellsAndOnlyAKillArea_exportsNoteAtTheKillArea(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleNonFacadeDungeonRoute();
            /** @var Floor $floor */
            $floor = $dungeonRoute->dungeon->floors()->firstOrFail();
            $this->createKillZone($dungeonRoute, 1, [Spell::SPELL_BLOODLUST], null, [
                'floor_id' => $floor->id,
                'lat'      => -100.5,
                'lng'      => 200.25,
            ]);

            $expected = Conversion::convertLatLngToMDTCoordinate(new LatLng(-100.5, 200.25, $floor));

            // Act
            $objects = $this->exportObjects($dungeonRoute);

            // Assert
            $this->assertCount(1, $objects);
            $this->assertSame($floor->mdt_sub_level ?? $floor->index, (int)$objects[0]['d'][2]);
            $this->assertEqualsWithDelta($expected['x'], (float)$objects[0]['d'][0], 0.01);
            $this->assertEqualsWithDelta($expected['y'], (float)$objects[0]['d'][1], 0.01);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function extractObjects_givenKillZoneWithEnemiesOnSeveralFloors_exportsNoteOnTheFloorWithMostEnemies(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            [$dungeonRoute, $majorityFloorEnemies, $minorityFloorEnemy] = $this->createRouteWithEnemiesOnTwoFloors();
            $this->createKillZone($dungeonRoute, 1, [Spell::SPELL_BLOODLUST], $minorityFloorEnemy, [], ...$majorityFloorEnemies);

            // Act
            $objects = $this->exportObjects($dungeonRoute);

            // Assert
            $this->assertCount(1, $objects);
            $this->assertNoteIsBelowEnemy($objects[0], $majorityFloorEnemies->sortBy('lat')->first());
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function extractObjects_givenKillAreaOnFloorWithoutAnyOfTheEnemies_exportsNoteOnTheFloorWithMostEnemies(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            [$dungeonRoute, $majorityFloorEnemies, $minorityFloorEnemy] = $this->createRouteWithEnemiesOnTwoFloors();
            $this->createKillZone($dungeonRoute, 1, [Spell::SPELL_BLOODLUST], null, [
                'floor_id' => $minorityFloorEnemy->floor_id,
                'lat'      => $minorityFloorEnemy->lat,
                'lng'      => $minorityFloorEnemy->lng,
            ], ...$majorityFloorEnemies);

            // Act
            $objects = $this->exportObjects($dungeonRoute);

            // Assert
            $this->assertCount(1, $objects);
            $this->assertNoteIsBelowEnemy($objects[0], $majorityFloorEnemies->sortBy('lat')->first());
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function extractObjects_givenKillAreaOnFloorWithSomeOfTheEnemies_exportsNoteOnTheKillAreaFloor(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            [$dungeonRoute, $majorityFloorEnemies, $minorityFloorEnemy] = $this->createRouteWithEnemiesOnTwoFloors();
            $this->createKillZone($dungeonRoute, 1, [Spell::SPELL_BLOODLUST], $minorityFloorEnemy, [
                'floor_id' => $minorityFloorEnemy->floor_id,
                'lat'      => $minorityFloorEnemy->lat,
                'lng'      => $minorityFloorEnemy->lng,
            ], ...$majorityFloorEnemies);

            // Act
            $objects = $this->exportObjects($dungeonRoute);

            // Assert
            $this->assertCount(1, $objects);
            $this->assertNoteIsBelowEnemy($objects[0], $minorityFloorEnemy);
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function extractObjects_givenFacadeRoute_exportsNoteOnTheFacadeFloorOfTheEnemy(): void
    {
        $dungeonRoute = null;

        try {
            // Arrange
            $coordinatesService                 = app(CoordinatesServiceInterface::class);
            [$dungeon, $mappingVersion, $enemy] = $this->findDungeon(
                facadeEnabled: true,
                minEnemies:    1,
                resolve:       fn(Dungeon $dungeon, MappingVersion $mappingVersion): ?Enemy => Conversion::hasMDTDungeonName($dungeon->key)
                    ? $this->findEnemyInsideFloorUnion($mappingVersion)
                    : null,
            );

            $dungeonRoute = DungeonRoute::factory()->create([
                'dungeon_id'         => $dungeon->id,
                'mapping_version_id' => $mappingVersion->id,
            ]);
            $this->createKillZone($dungeonRoute, 1, [Spell::SPELL_BLOODLUST], $enemy);

            $facadeLatLng = $coordinatesService->convertMapLocationToFacadeMapLocation(
                $mappingVersion,
                new LatLng($enemy->lat, $enemy->lng, $enemy->floor),
            );
            $facadeMdtCoordinates = Conversion::convertLatLngToMDTCoordinate($facadeLatLng);

            // Act
            $objects = $this->exportObjects($dungeonRoute);

            // Assert
            $this->assertCount(1, $objects);
            $this->assertTrue((bool)$facadeLatLng->getFloor()->facade);
            $this->assertSame($facadeLatLng->getFloor()->mdt_sub_level ?? $facadeLatLng->getFloor()->index, (int)$objects[0]['d'][2]);
            $this->assertNotEquals(
                [$facadeMdtCoordinates['x'], $facadeMdtCoordinates['y']],
                [(float)$objects[0]['d'][0], (float)$objects[0]['d'][1]],
            );

            $noteLatLng = $coordinatesService->convertFacadeMapLocationToMapLocation(
                $mappingVersion,
                Conversion::convertMDTCoordinateToLatLng(
                    ['x' => (float)$objects[0]['d'][0], 'y' => (float)$objects[0]['d'][1]],
                    $facadeLatLng->getFloor(),
                ),
            );
            $this->assertSame($enemy->floor_id, $noteLatLng->getFloor()->id);
            $this->assertLessThan(
                self::IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS,
                $coordinatesService->distanceIngameXY(
                    $coordinatesService->calculateIngameLocationForMapLocation($noteLatLng),
                    $coordinatesService->calculateIngameLocationForMapLocation(new LatLng($enemy->lat, $enemy->lng, $enemy->floor)),
                ),
            );
        } finally {
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function extractObjects_givenNonEnglishLocale_exportsTranslatedSpellNames(): void
    {
        $dungeonRoute = null;
        $locale       = app()->getLocale();

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $enemy        = $this->getSafeMdtEnemies($dungeonRoute)->first();
            $this->createKillZone($dungeonRoute, 1, [Spell::SPELL_BLOODLUST, Spell::SPELL_HEROISM], $enemy);

            app()->setLocale('de_DE');

            // Act
            $objects = $this->exportObjects($dungeonRoute);

            // Assert
            $this->assertCount(1, $objects);
            $this->assertSame("Kampfrausch\nHeldentum", $objects[0]['d'][4]);
        } finally {
            app()->setLocale($locale);
            $dungeonRoute?->delete();
        }
    }

    /**
     * @param  Collection<int, ImportWarning>|null $warnings
     * @return array<int, array<string, mixed>>
     */
    private function exportObjects(DungeonRoute $dungeonRoute, ?Collection $warnings = null): array
    {
        $encodedString = app()->make(MDTExportStringServiceInterface::class)
            ->setDungeonRoute($dungeonRoute)
            ->getEncodedString($warnings ?? collect(), false);

        return array_values($this->decode($encodedString)['objects'] ?? []);
    }

    /**
     * @param array<int, int>      $spellIds
     * @param array<string, mixed> $attributes
     */
    private function createKillZone(
        DungeonRoute $dungeonRoute,
        int          $index,
        array        $spellIds,
        ?Enemy       $enemy = null,
        array        $attributes = [],
        Enemy     ...$moreEnemies,
    ): KillZone {
        $enemies = $enemy === null ? $moreEnemies : [$enemy, ...$moreEnemies];

        return KillZone::factory()
            ->withEnemies(...$enemies)
            ->withSpells(...$spellIds)
            ->create(array_merge([
                'dungeon_route_id' => $dungeonRoute->id,
                'index'            => $index,
                'description'      => null,
                'floor_id'         => null,
                'lat'              => null,
                'lng'              => null,
            ], $attributes));
    }

    /**
     * @param array<string, mixed> $note
     */
    private function assertNoteIsBelowEnemy(array $note, Enemy $enemy): void
    {
        $coordinatesService = app(CoordinatesServiceInterface::class);
        $floor              = Floor::findOrFail($enemy->floor_id);
        $enemyLatLng        = new LatLng($enemy->lat, $enemy->lng, $floor);
        $enemyMdtCoordinate = Conversion::convertLatLngToMDTCoordinate($enemyLatLng);

        $this->assertTrue($note['n']);
        $this->assertTrue($note['d'][3]);
        $this->assertSame($floor->mdt_sub_level ?? $floor->index, (int)$note['d'][2]);
        $this->assertEqualsWithDelta($enemyMdtCoordinate['x'], (float)$note['d'][0], 0.1);
        $this->assertLessThan($enemyMdtCoordinate['y'], (float)$note['d'][1]);

        $noteLatLng = Conversion::convertMDTCoordinateToLatLng(['x' => (float)$note['d'][0], 'y' => (float)$note['d'][1]], $floor);
        $this->assertLessThan(
            self::IMPORT_NOTE_AS_KILL_ZONE_FEATURE_YARDS,
            $coordinatesService->distanceIngameXY(
                $coordinatesService->calculateIngameLocationForMapLocation($noteLatLng),
                $coordinatesService->calculateIngameLocationForMapLocation($enemyLatLng),
            ),
        );
    }

    private function isNotAtTheBottomOfTheMap(Enemy $enemy): bool
    {
        return $enemy->lat > CoordinatesService::MAP_MAX_LAT + 10;
    }

    /**
     * @return array{0: DungeonRoute, 1: Collection<int, Enemy>, 2: Enemy}
     */
    private function createRouteWithEnemiesOnTwoFloors(): array
    {
        [$dungeon, $mappingVersion, $enemiesByFloor] = $this->findDungeon(
            facadeEnabled:   false,
            minActiveFloors: 2,
            minEnemies:      3,
            resolve:         function (Dungeon $dungeon, MappingVersion $mappingVersion): ?Collection {
                if (!Conversion::hasMDTDungeonName($dungeon->key)) {
                    return null;
                }

                $enemiesByFloor = $mappingVersion->enemies()
                    ->with('floor')
                    ->get()
                    ->filter($this->isNotAtTheBottomOfTheMap(...))
                    ->groupBy('floor_id')
                    ->sortByDesc(static fn(Collection $enemies): int => $enemies->count())
                    ->values();

                return $enemiesByFloor->count() >= 2 && $enemiesByFloor->first()->count() >= 2 ? $enemiesByFloor : null;
            },
        );

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        return [$dungeonRoute, $enemiesByFloor->get(0)->take(2)->values(), $enemiesByFloor->get(1)->first()];
    }

    private function findEnemyInsideFloorUnion(MappingVersion $mappingVersion): ?Enemy
    {
        $coordinatesService = app(CoordinatesServiceInterface::class);

        return $mappingVersion->enemies()
            ->with('floor')
            ->get()
            ->first(fn(Enemy $enemy): bool => $this->isNotAtTheBottomOfTheMap($enemy) && $mappingVersion->getFloorUnionForLatLng(
                $coordinatesService,
                new LatLng($enemy->lat, $enemy->lng, $enemy->floor),
            ) !== null);
    }
}
