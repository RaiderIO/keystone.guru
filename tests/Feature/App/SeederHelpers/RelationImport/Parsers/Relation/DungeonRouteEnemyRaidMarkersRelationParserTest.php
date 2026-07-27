<?php

namespace Tests\Feature\App\SeederHelpers\RelationImport\Parsers\Relation;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteEnemyRaidMarker;
use App\Models\RaidMarker;
use App\SeederHelpers\RelationImport\Parsers\Relation\DungeonRouteEnemyRaidMarkersRelationParser;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SeederHelpers')]
final class DungeonRouteEnemyRaidMarkersRelationParserTest extends PublicTestCase
{
    private const string TEMP_ENEMIES_TABLE = 'enemies_temp';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Mirrors what DatabaseSeeder::getTempTableName(Enemy::class) resolves to while seeding -
        // only an id column is needed to exercise the "enemy not found" path this test covers.
        Schema::create(self::TEMP_ENEMIES_TABLE, function (Blueprint $table): void {
            $table->id();
        });
    }

    #[\Override]
    protected function tearDown(): void
    {
        Schema::dropIfExists(self::TEMP_ENEMIES_TABLE);

        parent::tearDown();
    }

    #[Test]
    public function parseRelation_givenUnresolvableEnemyId_skipsRowInsteadOfThrowing(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create();

        try {
            $parser = new DungeonRouteEnemyRaidMarkersRelationParser();

            // Decoded from JSON, mirroring how DungeonDataSeeder actually reads relation values from
            // the fixture files, rather than a PHP array literal with an overly-precise inferred shape
            /** @var array<string, mixed> $value */
            $value = json_decode(sprintf('[{"enemy_id": 1, "raid_marker_id": %d}]', RaidMarker::ALL['skull']), true);

            // A spy (rather than shouldReceive(), which replaces Log entirely with a strict mock)
            // only observes calls without failing the test over any OTHER log call made along the way
            $logSpy = Log::spy();

            // Act - the temp enemies table is empty, so enemy_id 1 cannot be resolved
            $result = $parser->parseRelation(DungeonRoute::class, ['id' => $dungeonRoute->id], 'enemy_raid_markers', $value);

            // Assert
            $this->assertSame(['id' => $dungeonRoute->id], $result);
            $this->assertSame(0, DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $dungeonRoute->id)->count());
            $logSpy->shouldHaveReceived('warning');
        } finally {
            DungeonRouteEnemyRaidMarker::where('dungeon_route_id', $dungeonRoute->id)->delete();
            $dungeonRoute->delete();
        }
    }
}
