<?php

namespace Tests\Feature\App\Service\MDT\Import;

use App\Logic\MDT\Conversion;
use App\Logic\Structs\LatLng;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
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

    #[Test]
    #[DataProvider('parseObjects_note_dataProvider')]
    public function parseObjects_givenANoteWithHtmlAssignedToAPull_importsThePullDescriptionWithoutTags(string $note, string $expectedText): void
    {
        // Arrange
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
        $this->assertSame($expectedText, $result->getKillZoneAttributes()->get(1)['description']);
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

    private function createImportStringObjects(
        MappingVersion $mappingVersion,
        Floor          $floor,
        Enemy          $enemy,
        string         $note,
    ): ImportStringObjects {
        $mdtCoordinate = Conversion::convertLatLngToMDTCoordinate(new LatLng($enemy->lat, $enemy->lng, $floor));

        return new ImportStringObjects(
            new Collection(),
            new Collection(),
            $mappingVersion->dungeon,
            $mappingVersion,
            new Collection(),
            [
                [
                    'n' => true,
                    'd' => [
                        $mdtCoordinate['x'],
                        $mdtCoordinate['y'],
                        $floor->mdt_sub_level ?? $floor->index,
                        true,
                        $note,
                    ],
                ],
            ],
        );
    }
}
