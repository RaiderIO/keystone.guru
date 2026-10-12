<?php

namespace Tests\Feature\App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAttribute;
use App\Models\Npc\Npc;
use App\Models\Npc\NpcDungeon;
use App\SeederHelpers\RelationImport\Parsers\Relation\PivotRelationParser;
use Database\Seeders\DatabaseSeeder;
use ErrorException;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeederHelpers')]
final class PivotRelationParserTest extends PublicTestCase
{
    private const FAKE_ID_OFFSET = 1000000;

    #[Test]
    public function parseRelation_givenParentKeyColumnAndRows_insertsEveryRowWithTheParentId(): void
    {
        // Arrange
        $parser    = $this->createDungeonRouteAttributesParser();
        $routeId   = self::FAKE_ID_OFFSET + 1;
        $modelData = ['id' => $routeId, 'title' => 'Route'];

        try {
            // Act
            $result = $parser->parseRelation(DungeonRoute::class, $modelData, 'routeattributesraw', [
                ['route_attribute_id' => 1],
                ['route_attribute_id' => 2],
            ]);

            // Assert
            $this->assertSame($modelData, $result);
            $this->assertSame(
                [1, 2],
                DungeonRouteAttribute::query()->where('dungeon_route_id', $routeId)->orderBy('route_attribute_id')->pluck('route_attribute_id')->all(),
            );
        } finally {
            DungeonRouteAttribute::query()->where('dungeon_route_id', $routeId)->delete();
        }
    }

    #[Test]
    public function parseRelation_givenParentKeyColumnAndStaleKeyInRow_overwritesItWithTheParentId(): void
    {
        // Arrange
        $parser     = $this->createDungeonRouteAttributesParser();
        $routeId    = self::FAKE_ID_OFFSET + 2;
        $staleRoute = self::FAKE_ID_OFFSET + 20;

        try {
            // Act
            $parser->parseRelation(DungeonRoute::class, ['id' => $routeId], 'routeattributesraw', [
                ['route_attribute_id' => 1, 'dungeon_route_id' => $staleRoute],
            ]);

            // Assert
            $this->assertSame(1, DungeonRouteAttribute::query()->where('dungeon_route_id', $routeId)->count());
            $this->assertSame(0, DungeonRouteAttribute::query()->where('dungeon_route_id', $staleRoute)->count());
        } finally {
            DungeonRouteAttribute::query()->whereIn('dungeon_route_id', [$routeId, $staleRoute])->delete();
        }
    }

    #[Test]
    public function parseRelation_givenParentKeyColumnAndNoRows_insertsNothing(): void
    {
        // Arrange
        $parser  = $this->createDungeonRouteAttributesParser();
        $routeId = self::FAKE_ID_OFFSET + 3;

        // Act
        $result = $parser->parseRelation(DungeonRoute::class, ['id' => $routeId], 'routeattributesraw', []);

        // Assert
        $this->assertSame(['id' => $routeId], $result);
        $this->assertSame(0, DungeonRouteAttribute::query()->where('dungeon_route_id', $routeId)->count());
    }

    #[Test]
    public function parseRelation_givenParentKeyColumnAndParentWithoutId_throws(): void
    {
        // Arrange
        $parser = $this->createDungeonRouteAttributesParser();

        // Assert
        $this->expectException(ErrorException::class);

        // Act
        $parser->parseRelation(DungeonRoute::class, ['title' => 'No id'], 'routeattributesraw', [
            ['route_attribute_id' => 1],
        ]);
    }

    #[Test]
    public function parseRelation_givenNoParentKeyColumn_insertsTheRowsUntouchedIntoTheStagingTable(): void
    {
        // Arrange
        $parser    = $this->createNpcDungeonsParser();
        $npcId     = self::FAKE_ID_OFFSET + 4;
        $modelData = ['id' => $npcId, 'name' => 'Npc'];
        $this->withStagingTable(NpcDungeon::class, function (string $stagingTable) use ($parser, $npcId, $modelData) {
            // Act
            $result = $parser->parseRelation(Npc::class, $modelData, 'npc_dungeons', [
                ['id' => self::FAKE_ID_OFFSET + 10, 'npc_id' => $npcId, 'dungeon_id' => 1],
                ['id' => self::FAKE_ID_OFFSET + 11, 'npc_id' => $npcId, 'dungeon_id' => 2],
            ]);

            // Assert
            $this->assertSame($modelData, $result);
            $this->assertSame(
                [1, 2],
                DB::table($stagingTable)->where('npc_id', $npcId)->orderBy('dungeon_id')->pluck('dungeon_id')->all(),
            );
            $this->assertSame(0, NpcDungeon::query()->where('npc_id', $npcId)->count());
        });
    }

    #[Test]
    public function parseRelation_givenNoParentKeyColumnAndNoRows_insertsNothing(): void
    {
        // Arrange
        $parser = $this->createNpcDungeonsParser();
        $this->withStagingTable(NpcDungeon::class, function (string $stagingTable) use ($parser) {
            // Act
            $result = $parser->parseRelation(Npc::class, ['id' => 1], 'npc_dungeons', []);

            // Assert
            $this->assertSame(['id' => 1], $result);
            $this->assertSame(0, DB::table($stagingTable)->count());
        });
    }

    #[Test]
    public function canParseModel_givenConfiguredParentModel_returnsTrueOnlyForThatModel(): void
    {
        // Arrange
        $parser = $this->createNpcDungeonsParser();

        // Act & Assert
        $this->assertTrue($parser->canParseModel(Npc::class));
        $this->assertFalse($parser->canParseModel(DungeonRoute::class));
    }

    #[Test]
    public function canParseRelation_givenConfiguredRelationName_returnsTrueOnlyForThatName(): void
    {
        // Arrange
        $parser = $this->createNpcDungeonsParser();

        // Act & Assert
        $this->assertTrue($parser->canParseRelation('npc_dungeons', []));
        $this->assertFalse($parser->canParseRelation('npc_healths', []));
    }

    private function createDungeonRouteAttributesParser(): PivotRelationParser
    {
        return new PivotRelationParser(DungeonRoute::class, 'routeattributesraw', DungeonRouteAttribute::class, 'dungeon_route_id');
    }

    private function createNpcDungeonsParser(): PivotRelationParser
    {
        return new PivotRelationParser(Npc::class, 'npc_dungeons', NpcDungeon::class);
    }

    /**
     * Creates the empty staging table the seeder would have made for the pivot, runs the callback, and drops it again.
     *
     * @param class-string           $pivotModelClass
     * @param callable(string): void $callback        receives the staging table name
     */
    private function withStagingTable(string $pivotModelClass, callable $callback): void
    {
        $table   = (new $pivotModelClass())->getTable();
        $staging = DatabaseSeeder::getTempTableName($pivotModelClass);

        DB::statement(sprintf('DROP TABLE IF EXISTS `%s`', $staging));
        DB::statement(sprintf('CREATE TABLE `%s` LIKE `%s`', $staging, $table));

        try {
            $callback($staging);
        } finally {
            DB::statement(sprintf('DROP TABLE IF EXISTS `%s`', $staging));
        }
    }
}
