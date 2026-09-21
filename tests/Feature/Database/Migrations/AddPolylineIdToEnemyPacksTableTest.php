<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\EnemyPack;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use stdClass;
use Tests\TestCases\PublicTestCase;

/**
 * Exercises the migration's backfill step only: the schema half is DDL, which commits implicitly and cannot be
 * rolled back on the persistent test database. Every test runs inside a transaction that is always rolled back,
 * which also disposes of the fixture packs.
 */
#[Group('MappingVersion')]
final class AddPolylineIdToEnemyPacksTableTest extends PublicTestCase
{
    private const MIGRATION = 'migrations/2026_09_21_180000_add_polyline_id_to_enemy_packs_table.php';

    private const VERTICES_JSON = '[{"lat":-10.5,"lng":20.25},{"lat":-12,"lng":22},{"lat":-14.75,"lng":19}]';

    #[Test]
    public function backfillPolylines_givenPackWithoutPolyline_createsPolylineFromThePacksShape(): void
    {
        // Arrange
        DB::beginTransaction();

        try {
            $enemyPackId = $this->insertEnemyPack('#ff002b');

            // Act
            $this->runBackfill();

            // Assert
            $enemyPack = $this->findEnemyPack($enemyPackId);
            $this->assertNotNull($enemyPack->polyline_id);

            $polyline = $this->findPolyline($enemyPack->polyline_id);
            $this->assertSame(EnemyPack::class, $polyline->model_class);
            $this->assertSame($enemyPackId, (int)$polyline->model_id);
            $this->assertSame('#ff002b', $polyline->color);
            $this->assertNull($polyline->color_animated);
            $this->assertSame(1, (int)$polyline->weight);
            $this->assertSame(self::VERTICES_JSON, $polyline->vertices_json);
        } finally {
            DB::rollBack();
        }
    }

    #[Test]
    public function backfillPolylines_givenPackWithoutColor_usesTheDefaultPackColor(): void
    {
        // Arrange
        DB::beginTransaction();

        try {
            $enemyPackId = $this->insertEnemyPack(null);

            // Act
            $this->runBackfill();

            // Assert
            $polyline = $this->findPolyline($this->findEnemyPack($enemyPackId)->polyline_id);
            $this->assertSame('#5993D2', $polyline->color);
        } finally {
            DB::rollBack();
        }
    }

    #[Test]
    public function backfillPolylines_givenSecondRun_createsNoAdditionalPolylines(): void
    {
        // Arrange
        DB::beginTransaction();

        try {
            $enemyPackId = $this->insertEnemyPack(null);
            $this->runBackfill();
            $polylineId    = $this->findEnemyPack($enemyPackId)->polyline_id;
            $polylineCount = DB::table('polylines')->count();

            // Act
            $this->runBackfill();

            // Assert
            $this->assertSame($polylineCount, DB::table('polylines')->count());
            $this->assertSame($polylineId, $this->findEnemyPack($enemyPackId)->polyline_id);
        } finally {
            DB::rollBack();
        }
    }

    #[Test]
    public function backfillPolylines_givenPackWhosePolylineWasCreatedButNotLinked_linksTheExistingPolyline(): void
    {
        // Arrange
        DB::beginTransaction();

        try {
            $enemyPackId        = $this->insertEnemyPack(null);
            $existingPolylineId = DB::table('polylines')->insertGetId([
                'model_id'      => $enemyPackId,
                'model_class'   => EnemyPack::class,
                'color'         => '#123456',
                'weight'        => 1,
                'vertices_json' => self::VERTICES_JSON,
            ]);
            $polylineCount = DB::table('polylines')->count();

            // Act
            $this->runBackfill();

            // Assert
            $this->assertSame($polylineCount, DB::table('polylines')->count());
            $this->assertSame($existingPolylineId, (int)$this->findEnemyPack($enemyPackId)->polyline_id);
        } finally {
            DB::rollBack();
        }
    }

    #[Test]
    public function backfillPolylines_givenPackThatAlreadyHasAPolyline_leavesItAlone(): void
    {
        // Arrange
        DB::beginTransaction();

        try {
            $enemyPackId = $this->insertEnemyPack(null);
            $polylineId  = DB::table('polylines')->insertGetId([
                'model_id'      => $enemyPackId,
                'model_class'   => EnemyPack::class,
                'color'         => '#123456',
                'weight'        => 1,
                'vertices_json' => '[]',
            ]);
            DB::table('enemy_packs')->where('id', $enemyPackId)->update(['polyline_id' => $polylineId]);

            // Act
            $this->runBackfill();

            // Assert
            $polyline = $this->findPolyline($polylineId);
            $this->assertSame('#123456', $polyline->color);
            $this->assertSame('[]', $polyline->vertices_json);
            $this->assertSame(1, DB::table('polylines')
                ->where('model_class', EnemyPack::class)
                ->where('model_id', $enemyPackId)
                ->count());
        } finally {
            DB::rollBack();
        }
    }

    private function runBackfill(): void
    {
        $migration = require database_path(self::MIGRATION);

        new ReflectionMethod($migration, 'backfillPolylines')->invoke($migration);
    }

    private function insertEnemyPack(?string $color): int
    {
        /** @var MappingVersion $mappingVersion */
        $mappingVersion = MappingVersion::query()->firstOrFail();
        /** @var Floor $floor */
        $floor = $mappingVersion->dungeon->floors()->firstOrFail();

        return DB::table('enemy_packs')->insertGetId([
            'mapping_version_id' => $mappingVersion->id,
            'floor_id'           => $floor->id,
            'faction'            => 'any',
            'color'              => $color,
            'label'              => 'Enemy pack',
            'vertices_json'      => self::VERTICES_JSON,
        ]);
    }

    private function findEnemyPack(int $enemyPackId): stdClass
    {
        /** @var stdClass */
        return DB::table('enemy_packs')->find($enemyPackId);
    }

    private function findPolyline(int $polylineId): stdClass
    {
        /** @var stdClass */
        return DB::table('polylines')->find($polylineId);
    }
}
