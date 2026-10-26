<?php

namespace Tests\Feature\App\Service\MDT\Import;

use App\Logic\MDT\Conversion;
use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Models\Spell\KnownSpell;
use App\Service\Coordinates\CoordinatesServiceInterface;
use App\Service\MDT\Import\ObjectImporter;
use App\Service\MDT\Models\ImportStringObjects;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('MDT')]
#[Group('ObjectImporter')]
final class ObjectImporterTest extends PublicTestCase
{
    use ProvidesDungeon;

    /** @var array{x: float, y: float} Far outside the map, so inside no floor behind a facade */
    private const array OUT_OF_BOUNDS_MDT_COORDINATE = ['x' => 100000.0, 'y' => 100000.0];

    #[Test]
    #[DataProvider('parseObjects_note_dataProvider')]
    public function parseObjects_givenANoteWithHtml_importsTheMapIconCommentWithoutTags(string $note, string $expectedText): void
    {
        // Arrange
        [$mappingVersion, $enemy, $floor] = $this->findDungeonWithEnemy();
        $importStringObjects              = $this->createImportStringObjects($mappingVersion, $floor, $enemy, $note);

        // Act
        $result = app()->make(ObjectImporter::class)->parseObjects($importStringObjects, false);

        // Assert
        $this->assertCount(1, $result->getMapIcons());
        $this->assertSame($expectedText, $result->getMapIcons()->first()['comment']);
    }

    /**
     * The map icons are bulk-inserted past the model, and production's `map_icons.permanent_tooltip`
     * has no column default (the schema dumps do), so strict mode rejects a row without it.
     */
    #[Test]
    public function parseObjects_givenANote_setsPermanentTooltipOnTheMapIcon(): void
    {
        // Arrange
        [$mappingVersion, $enemy, $floor] = $this->findDungeonWithEnemy();
        $importStringObjects              = $this->createImportStringObjects($mappingVersion, $floor, $enemy, 'note');

        // Act
        $result = app()->make(ObjectImporter::class)->parseObjects($importStringObjects, false);

        // Assert
        $this->assertCount(1, $result->getMapIcons());
        $this->assertSame(0, $result->getMapIcons()->first()['permanent_tooltip'] ?? null);
    }

    #[Test]
    public function parseObjects_givenANoteWithHtmlAssignedToAPull_importsThePullDescriptionUnchanged(): void
    {
        // Arrange
        $note = '<b>x</b>';

        [$mappingVersion, $enemy, $floor] = $this->findDungeonWithEnemy();
        $importStringObjects              = $this->createImportStringObjects($mappingVersion, $floor, $enemy, $note);
        $importStringObjects->getKillZoneAttributes()->put(1, [
            'index'           => 1,
            'killZoneEnemies' => [['enemy' => $enemy]],
            'spells'          => [],
        ]);

        // Act
        $result = app()->make(ObjectImporter::class)->parseObjects($importStringObjects, true);

        // Assert
        $this->assertCount(0, $result->getMapIcons());
        $this->assertSame($note, $result->getKillZoneAttributes()->get(1)['description']);
    }

    #[Test]
    public function parseObjects_givenALineEntirelyOutOfBoundsOnTheFacade_skipsTheLineWithAWarning(): void
    {
        // Arrange
        [$mappingVersion, $facadeFloor] = $this->findFacadeDungeonWithEnemy();
        $importStringObjects            = $this->createImportStringObjectsWithLine(
            $mappingVersion,
            $facadeFloor,
            [self::OUT_OF_BOUNDS_MDT_COORDINATE, self::OUT_OF_BOUNDS_MDT_COORDINATE],
            isArrow: false,
        );

        // Act
        $result = app()->make(ObjectImporter::class)->parseObjects($importStringObjects, false);

        // Assert
        $this->assertCount(0, $result->getPaths());
        $this->assertSame(
            [__('services.mdt.io.import_string.line_out_of_bounds')],
            $result->getWarnings()->map(static fn(ImportWarning $warning) => $warning->getMessage())->values()->all(),
        );
    }

    #[Test]
    public function parseObjects_givenAnArrowEntirelyOutOfBoundsOnTheFacade_skipsTheArrowWithAWarning(): void
    {
        // Arrange
        [$mappingVersion, $facadeFloor] = $this->findFacadeDungeonWithEnemy();
        $importStringObjects            = $this->createImportStringObjectsWithLine(
            $mappingVersion,
            $facadeFloor,
            [self::OUT_OF_BOUNDS_MDT_COORDINATE, self::OUT_OF_BOUNDS_MDT_COORDINATE],
            isArrow: true,
        );

        // Act
        $result = app()->make(ObjectImporter::class)->parseObjects($importStringObjects, false);

        // Assert
        $this->assertCount(0, $result->getArrows());
        $this->assertSame(
            [__('services.mdt.io.import_string.arrow_out_of_bounds')],
            $result->getWarnings()->map(static fn(ImportWarning $warning) => $warning->getMessage())->values()->all(),
        );
    }

    #[Test]
    public function parseObjects_givenALineInsideAFloorOnTheFacade_importsTheLineOnThatFloor(): void
    {
        // Arrange
        [$mappingVersion, $facadeFloor, $enemy, $enemyMdtCoordinate] = $this->findFacadeDungeonWithEnemy();
        $importStringObjects                                         = $this->createImportStringObjectsWithLine(
            $mappingVersion,
            $facadeFloor,
            [$enemyMdtCoordinate, $enemyMdtCoordinate],
            isArrow: false,
        );

        // Act
        $result = app()->make(ObjectImporter::class)->parseObjects($importStringObjects, false);

        // Assert
        $this->assertCount(0, $result->getWarnings());
        $this->assertCount(1, $result->getPaths());
        $this->assertSame($enemy->floor_id, $result->getPaths()->first()['floor_id']);
    }

    #[Test]
    public function parseObjects_givenALineStartingOutOfBoundsOnTheFacade_importsTheLineOnTheFloorOfItsFirstPointInBounds(): void
    {
        // Arrange
        [$mappingVersion, $facadeFloor, $enemy, $enemyMdtCoordinate] = $this->findFacadeDungeonWithEnemy(
            requireOutOfBoundsCoordinateMovesOnTheEnemyFloor: true,
        );
        $importStringObjects = $this->createImportStringObjectsWithLine(
            $mappingVersion,
            $facadeFloor,
            [self::OUT_OF_BOUNDS_MDT_COORDINATE, $enemyMdtCoordinate],
            isArrow: false,
        );
        $outOfBoundsFacadeLatLng = Conversion::convertMDTCoordinateToLatLng(self::OUT_OF_BOUNDS_MDT_COORDINATE, $facadeFloor);
        $expectedFirstVertex     = app(CoordinatesServiceInterface::class)->convertFacadeMapLocationToMapLocation(
            $mappingVersion,
            $outOfBoundsFacadeLatLng,
            Floor::query()->findOrFail($enemy->floor_id),
        )->toArray();

        // Act
        $result = app()->make(ObjectImporter::class)->parseObjects($importStringObjects, false);

        // Assert - the point before the first one in bounds is in that floor's coordinates too, not the facade's
        $this->assertCount(1, $result->getPaths());
        $path = $result->getPaths()->first();
        $this->assertSame($enemy->floor_id, $path['floor_id']);
        $this->assertNotEquals($outOfBoundsFacadeLatLng->toArray(), $expectedFirstVertex);
        $this->assertEquals($expectedFirstVertex, json_decode($path['polyline']['vertices_json'], true)[0]);
    }

    #[Test]
    public function parseObjects_givenANoteOutOfBoundsOnTheFacade_skipsTheNoteWithAWarning(): void
    {
        // Arrange
        [$mappingVersion, $facadeFloor] = $this->findFacadeDungeonWithEnemy();
        $importStringObjects            = new ImportStringObjects(
            new Collection(),
            new Collection(),
            $mappingVersion->dungeon,
            $mappingVersion,
            new Collection(),
            [
                [
                    'n' => true,
                    'd' => [
                        self::OUT_OF_BOUNDS_MDT_COORDINATE['x'],
                        self::OUT_OF_BOUNDS_MDT_COORDINATE['y'],
                        $facadeFloor->mdt_sub_level ?? $facadeFloor->index,
                        true,
                        'note',
                    ],
                ],
            ],
        );

        // Act
        $result = app()->make(ObjectImporter::class)->parseObjects($importStringObjects, false);

        // Assert
        $this->assertCount(0, $result->getMapIcons());
        $this->assertSame(
            [__('services.mdt.io.import_string.object_out_of_bounds', ['comment' => 'note'])],
            $result->getWarnings()->map(static fn(ImportWarning $warning) => $warning->getMessage())->values()->all(),
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function parseObjects_note_dataProvider(): array
    {
        return [
            'plain text'                  => ['Stack on the tank', 'Stack on the tank'],
            'bold'                        => ['<b>x</b>', 'x'],
            'image with an event handler' => ['Kick<img src=x onerror=alert(1)>', 'Kick'],
            'script'                      => ['<script>alert(1)</script>', 'alert(1)'],
            'less than that is no tag'    => ['a < b', 'a < b'],
        ];
    }

    /**
     * @param array<int, string> $expectedTextTranslationKeys Translated into the %s placeholders of $expectedText
     */
    #[Test]
    #[DataProvider('parseObjects_mapText_dataProvider')]
    public function parseObjects_givenAMapText_importsTheMapIconCommentAsPlainText(
        string $mapText,
        string $expectedText,
        array  $expectedTextTranslationKeys = [],
    ): void {
        // Arrange
        [$mappingVersion, $enemy, $floor] = $this->findDungeonWithEnemy();
        $importStringObjects              = $this->createImportStringObjects($mappingVersion, $floor, $enemy, $mapText, isMapText: true);

        // Act
        $result = app()->make(ObjectImporter::class)->parseObjects($importStringObjects, false);

        // Assert
        $this->assertCount(1, $result->getMapIcons());
        $this->assertSame(
            sprintf($expectedText, ...array_map(static fn(string $key): string => __($key), $expectedTextTranslationKeys)),
            $result->getMapIcons()->first()['comment'],
        );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2?: array<int, string>}>
     */
    public static function parseObjects_mapText_dataProvider(): array
    {
        return [
            'plain text'                   => ['Stack on the tank', 'Stack on the tank'],
            'colour run'                   => ['|cffff3030Lust|r here', 'Lust here'],
            'unterminated colour run'      => ['|cFFFF3030Lust here', 'Lust here'],
            'escaped pipe'                 => ['a || b', 'a | b'],
            'escaped pipe before a colour' => ['||cffff3030', '|cffff3030'],
            'known spell'                  => ['Lust {spell:2825} here', 'Lust Bloodlust here'],
            'known spell with a size'      => ['{spell:2825:16} now', 'Bloodlust now'],
            'unknown spell'                => ['Lust {spell:999999999} here', 'Lust here'],
            'spell without an id'          => ['{spell:abc}', '{spell:abc}'],
            'file icon'                    => ['Go {icon:134400} here', 'Go here'],
            'spec icon'                    => ['{spec:250} tanks', 'tanks'],
            'raid marker index'            => ['Kill {rt8} first', 'Kill %s first', ['mapicontypes.raid_marker_skull']],
            'raid marker name'             => ['Kill {Skull} first', 'Kill %s first', ['mapicontypes.raid_marker_skull']],
            'raid marker alias'            => ['{x} last', '%s last', ['mapicontypes.raid_marker_cross']],
            'role'                         => ['{tank} pulls', 'tank pulls'],
            'class'                        => ['{Death Knight} grips', 'Death Knight grips'],
            'unknown tag'                  => ['{foo} and {foo:bar}', '{foo} and {foo:bar}'],
            'multiple lines'               => ["  {icon:1}  First \n{rt1}  ", "First\n%s", ['mapicontypes.raid_marker_star']],
        ];
    }

    #[Test]
    public function parseObjects_givenAMapTextOfOnlyIcons_skipsTheMapText(): void
    {
        // Arrange
        [$mappingVersion, $enemy, $floor] = $this->findDungeonWithEnemy();
        $importStringObjects              = $this->createImportStringObjects($mappingVersion, $floor, $enemy, '{icon:134400} |cffffffff|r', isMapText: true);

        // Act
        $result = app()->make(ObjectImporter::class)->parseObjects($importStringObjects, false);

        // Assert
        $this->assertCount(0, $result->getMapIcons());
        $this->assertCount(0, $result->getWarnings());
    }

    #[Test]
    public function parseObjects_givenANoteWithMapTextMarkup_importsTheMapIconCommentUnchanged(): void
    {
        // Arrange
        $note = '|cffff3030Lust|r {rt8}';

        [$mappingVersion, $enemy, $floor] = $this->findDungeonWithEnemy();
        $importStringObjects              = $this->createImportStringObjects($mappingVersion, $floor, $enemy, $note);

        // Act
        $result = app()->make(ObjectImporter::class)->parseObjects($importStringObjects, false);

        // Assert
        $this->assertCount(1, $result->getMapIcons());
        $this->assertSame($note, $result->getMapIcons()->first()['comment']);
    }

    #[Test]
    public function parseObjects_givenAMapTextOfASpellTagNextToAPull_assignsTheSpellToThePull(): void
    {
        // Arrange
        [$mappingVersion, $enemy, $floor] = $this->findDungeonWithEnemy();
        $importStringObjects              = $this->createImportStringObjects($mappingVersion, $floor, $enemy, '|cffff3030{spell:2825}|r', isMapText: true);
        $importStringObjects->getKillZoneAttributes()->put(1, [
            'index'           => 1,
            'killZoneEnemies' => [['enemy' => $enemy]],
            'spells'          => [],
        ]);

        // Act
        $result = app()->make(ObjectImporter::class)->parseObjects($importStringObjects, true);

        // Assert
        $this->assertCount(0, $result->getMapIcons());
        $this->assertSame([['spell_id' => KnownSpell::Bloodlust->value]], $result->getKillZoneAttributes()->get(1)['spells']);
    }

    /**
     * @return array{0: MappingVersion, 1: Enemy, 2: Floor}
     */
    private function findDungeonWithEnemy(): array
    {
        /** @var Dungeon $dungeon */
        /** @var MappingVersion $mappingVersion */
        /** @var Enemy $enemy */
        [$dungeon, $mappingVersion, $enemy] = $this->findDungeon(
            facadeEnabled: false,
            minEnemies: 1,
            resolve: static fn(Dungeon $dungeon, MappingVersion $mappingVersion): ?Enemy => $mappingVersion->enemies()
                ->whereHas('floor', static fn($query) => $query->where('facade', false))
                ->first(),
        );

        /** @var Floor $floor */
        $floor = Floor::query()->findOrFail($enemy->floor_id);

        return [$mappingVersion, $enemy, $floor];
    }

    /**
     * @param  bool                                                                       $requireOutOfBoundsCoordinateMovesOnTheEnemyFloor Only accept an enemy on a floor where forcing the facade conversion of OUT_OF_BOUNDS_MDT_COORDINATE yields a different lat/lng; an identity floor union, or none targeting the floor, leaves it unchanged.
     * @return array{0: MappingVersion, 1: Floor, 2: Enemy, 3: array{x: float, y: float}}
     */
    private function findFacadeDungeonWithEnemy(bool $requireOutOfBoundsCoordinateMovesOnTheEnemyFloor = false): array
    {
        /** @var Dungeon $dungeon */
        /** @var MappingVersion $mappingVersion */
        /** @var Enemy $enemy */
        [$dungeon, $mappingVersion, $enemy] = $this->findDungeon(
            facadeEnabled: true,
            minEnemies: 1,
            resolve: static function (Dungeon $dungeon, MappingVersion $mappingVersion) use ($requireOutOfBoundsCoordinateMovesOnTheEnemyFloor): ?Enemy {
                $enemies = $mappingVersion->enemies()
                    ->whereHas('floor', static fn($query) => $query->where('facade', false));

                if (!$requireOutOfBoundsCoordinateMovesOnTheEnemyFloor) {
                    return $enemies->first();
                }

                /** @var Floor|null $facadeFloor */
                $facadeFloor = $dungeon->floors()->where('facade', true)->first();
                if ($facadeFloor === null) {
                    return null;
                }

                $outOfBoundsFacadeLatLng = Conversion::convertMDTCoordinateToLatLng(self::OUT_OF_BOUNDS_MDT_COORDINATE, $facadeFloor);

                /** @var Enemy|null $enemy */
                $enemy = $enemies->get()->unique('floor_id')->first(
                    static fn(Enemy $enemy): bool => app(CoordinatesServiceInterface::class)->convertFacadeMapLocationToMapLocation(
                        $mappingVersion,
                        $outOfBoundsFacadeLatLng,
                        Floor::query()->findOrFail($enemy->floor_id),
                    )->toArray() != $outOfBoundsFacadeLatLng->toArray(),
                );

                return $enemy;
            },
        );

        $facadeFloor = $dungeon->getFacadeFloor();
        $this->assertNotNull($facadeFloor);

        /** @var Floor $floor */
        $floor        = Floor::query()->findOrFail($enemy->floor_id);
        $facadeLatLng = app(CoordinatesServiceInterface::class)->convertMapLocationToFacadeMapLocation(
            $mappingVersion,
            new LatLng($enemy->lat, $enemy->lng, $floor),
        );
        $enemyMdtCoordinate = Conversion::convertLatLngToMDTCoordinate($facadeLatLng);

        return [$mappingVersion, $facadeFloor, $enemy, $enemyMdtCoordinate];
    }

    /**
     * @param array<int, array{x: float, y: float}> $mdtCoordinates
     */
    private function createImportStringObjectsWithLine(
        MappingVersion $mappingVersion,
        Floor          $floor,
        array          $mdtCoordinates,
        bool           $isArrow,
    ): ImportStringObjects {
        $line = [];
        foreach ($mdtCoordinates as $mdtCoordinate) {
            $line[] = $mdtCoordinate['x'];
            $line[] = $mdtCoordinate['y'];
        }

        return new ImportStringObjects(
            new Collection(),
            new Collection(),
            $mappingVersion->dungeon,
            $mappingVersion,
            new Collection(),
            [
                array_filter([
                    'l' => $line,
                    'd' => [3, 1.1, $floor->mdt_sub_level ?? $floor->index, true, 'ff0000', 0, false],
                    't' => $isArrow ? [0.5] : null,
                ], static fn($value) => $value !== null),
            ],
        );
    }

    private function createImportStringObjects(
        MappingVersion $mappingVersion,
        Floor          $floor,
        Enemy          $enemy,
        string         $note,
        bool           $isMapText = false,
    ): ImportStringObjects {
        $mdtCoordinate = Conversion::convertLatLngToMDTCoordinate(new LatLng($enemy->lat, $enemy->lng, $floor));

        return new ImportStringObjects(
            new Collection(),
            new Collection(),
            $mappingVersion->dungeon,
            $mappingVersion,
            new Collection(),
            [
                array_merge([
                    'n' => true,
                    'd' => [
                        $mdtCoordinate['x'],
                        $mdtCoordinate['y'],
                        $floor->mdt_sub_level ?? $floor->index,
                        true,
                        $note,
                    ],
                ], $isMapText ? ['tx' => true] : []),
            ],
        );
    }
}
