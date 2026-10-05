<?php

namespace Tests\Feature\App\Service\MDT;

use App\Logic\MDT\Exception\ImportWarning;
use App\Logic\MDT\IO\MDT2Codec;
use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Service\MDT\MDTImportStringServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Values an MDT string can carry that do not fit the column they are imported into.
 */
#[Group('UsesLua')]
#[Group('MDTImportStringService')]
final class MDTImportStringServiceColumnLimitsTest extends MDTImportStringServiceTestBase
{
    #[Test]
    public function getDungeonRoute_givenAKeyLevelNoKeystoneReaches_usesTheSeasonsKeyLevels(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute          = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $decoded               = $this->decode($this->exportDungeonRouteToString($dungeonRoute));
            $decoded['difficulty'] = 99999999999;

            // Act - the legacy format, since the MDT2 codec only encodes 32-bit integers
            $importedRoute = $this->importStringToDungeonRoute($this->encode($decoded));

            // Assert - the seed does not promise the dungeon a season, and without one the import uses +2
            $season = app(SeasonServiceInterface::class)->getMostRecentSeasonForDungeon($dungeonRoute->dungeon);
            $this->assertSame($season === null ? 2 : $season->key_level_min, $importedRoute->level_min);
            $this->assertSame($season === null ? 2 : $season->key_level_max, $importedRoute->level_max);
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenAKeyLevel_usesIt(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute          = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $decoded               = $this->decode($this->exportDungeonRouteToString($dungeonRoute));
            $decoded['difficulty'] = 17;

            // Act
            $importedRoute = $this->importStringToDungeonRoute(new MDT2Codec()->encode($decoded));

            // Assert
            $this->assertSame(17, $importedRoute->level_min);
            $this->assertSame(17, $importedRoute->level_max);
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenANoteLongerThanAMapIconCommentHolds_truncatesTheComment(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $floor        = $dungeonRoute->dungeon->floors()->where('facade', false)->firstOrFail();
            $comment      = str_repeat('😀', MapIcon::COMMENT_MAX_LENGTH + 10);
            MapIcon::factory()->create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $floor->id,
                'map_icon_type_id' => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_COMMENT],
                'lat'              => -100,
                'lng'              => 100,
                'comment'          => 'placeholder',
            ]);
            $decoded = $this->decode($this->exportDungeonRouteToString($dungeonRoute));
            foreach ($decoded['objects'] as $index => $object) {
                if (!isset($object['l'])) {
                    $decoded['objects'][$index]['d'][4] = $comment;
                }
            }

            // Act
            $importedRoute = $this->importStringToDungeonRoute(new MDT2Codec()->encode($decoded));

            // Assert
            $this->assertSame(
                [mb_substr($comment, 0, MapIcon::COMMENT_MAX_LENGTH)],
                $importedRoute->mapIcons()->pluck('comment')->all(),
            );
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }

    #[Test]
    public function getDungeonRoute_givenALineWithMorePointsThanAPolylineHolds_leavesItOutWithAWarning(): void
    {
        $dungeonRoute  = null;
        $importedRoute = null;

        try {
            // Arrange
            $dungeonRoute = $this->getMDTCompatibleDungeonRouteWithSafeEnemies();
            $this->createBrushlineForRoute($dungeonRoute);
            $this->createBrushlineForRoute($dungeonRoute);
            $decoded = $this->decode($this->exportDungeonRouteToString($dungeonRoute));

            $lineIndexes = [];
            foreach ($decoded['objects'] as $index => $object) {
                if (isset($object['l'])) {
                    $lineIndexes[] = $index;
                }
            }
            $this->assertCount(2, $lineIndexes, 'The export must hold both brushlines');
            $points                                   = array_slice($decoded['objects'][$lineIndexes[0]]['l'], 0, 4);
            $decoded['objects'][$lineIndexes[0]]['l'] = array_merge(...array_fill(0, 1500, $points));
            $warnings                                 = new Collection();

            // Act
            $importedRoute = app()->make(MDTImportStringServiceInterface::class)
                ->setEncodedString(new MDT2Codec()->encode($decoded))
                ->getDungeonRoute(warnings: $warnings, errors: new Collection(), sandbox: true, save: false);

            // Assert
            $this->assertSame(1, $importedRoute->brushlines()->count());
            $this->assertTrue($warnings->contains(
                static fn(ImportWarning $warning) => $warning->getMessage() === __('services.mdt.io.import_string.line_too_long'),
            ));
        } finally {
            $importedRoute?->delete();
            $dungeonRoute?->delete();
        }
    }
}
