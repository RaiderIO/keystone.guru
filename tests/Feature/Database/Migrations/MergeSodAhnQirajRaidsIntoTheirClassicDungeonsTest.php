<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\GameVersion\GameVersion;
use App\Models\KillZone\KillZone;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use Database\Factories\FloorFactory;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The seeded database holds no Season of Discovery copy of Ruins of Ahn'Qiraj, so each test
 * creates one under its key. The migration's down() is a no-op and it rewrites the real Classic
 * dungeon, so every test runs inside a transaction that is always rolled back, which also disposes of
 * the fixtures.
 */
#[Group('MappingVersion')]
#[Group('MergeSodAhnQiraj')]
final class MergeSodAhnQirajRaidsIntoTheirClassicDungeonsTest extends PublicTestCase
{
    private const string MIGRATION = 'migrations/2026_10_02_200000_merge_sod_ahnqiraj_raids_into_their_classic_dungeons.php';

    private const string SOURCE_KEY = 'ruins_of_ahnqiraj_sod';
    private const string TARGET_KEY = 'ruins_of_ahnqiraj_classic';

    private const int SOURCE_ZONE_ID     = 999901;
    private const int SOURCE_MAP_ID      = 999902;
    private const int SOURCE_INSTANCE_ID = 999903;
    private const int SOURCE_UI_MAP_ID   = 999904;
    private const int SOURCE_VIEWS       = 7;

    private const int SPELL_ID_ON_BOTH        = 999905;
    private const int SPELL_ID_ON_SOURCE_ONLY = 999906;

    private const int NPC_ID_ON_BOTH = 999907;

    #[Test]
    public function up_givenRouteOnSourceDungeon_movesRouteAndItsKillZoneToTarget(): void
    {
        DB::beginTransaction();

        try {
            // Arrange
            $target       = $this->getTargetDungeon();
            $sourceId     = $this->createSourceDungeon();
            $sourceFloor  = $this->createSourceFloor($sourceId, 1);
            $dungeonRoute = DungeonRoute::factory()->create([
                'dungeon_id'         => $sourceId,
                'mapping_version_id' => $this->createSourceMappingVersion($sourceId)->id,
            ]);
            $killZone = KillZone::factory()->create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $sourceFloor->id,
            ]);

            // Act
            $this->runMigration();

            // Assert
            $this->assertSame($target->id, $dungeonRoute->fresh()->dungeon_id);
            $this->assertSame($this->getTargetFloor($target)->id, $killZone->fresh()->floor_id);
        } finally {
            DB::rollBack();
        }
    }

    #[Test]
    public function up_givenSourceMappingVersion_movesItToTargetDungeon(): void
    {
        DB::beginTransaction();

        try {
            // Arrange
            $target         = $this->getTargetDungeon();
            $sourceId       = $this->createSourceDungeon();
            $mappingVersion = $this->createSourceMappingVersion($sourceId);

            // Act
            $this->runMigration();

            // Assert
            $this->assertSame($target->id, $mappingVersion->fresh()->dungeon_id);
        } finally {
            DB::rollBack();
        }
    }

    #[Test]
    public function up_givenSourceFloorWithCounterpart_copiesInGameDataOntoTargetFloorAndDeletesSourceFloor(): void
    {
        DB::beginTransaction();

        try {
            // Arrange
            $target      = $this->getTargetDungeon();
            $sourceId    = $this->createSourceDungeon();
            $sourceFloor = $this->createSourceFloor($sourceId, 1);

            // Act
            $this->runMigration();

            // Assert
            $targetFloor = $this->getTargetFloor($target);
            $this->assertSame(self::SOURCE_UI_MAP_ID, $targetFloor->ui_map_id);
            $this->assertEquals($sourceFloor->ingame_min_x, $targetFloor->ingame_min_x);
            $this->assertEquals($sourceFloor->ingame_max_y, $targetFloor->ingame_max_y);
            $this->assertNull(Floor::query()->find($sourceFloor->id));
        } finally {
            DB::rollBack();
        }
    }

    #[Test]
    public function up_givenSourceFloorWithoutCounterpart_movesFloorToTargetDungeon(): void
    {
        DB::beginTransaction();

        try {
            // Arrange
            $target   = $this->getTargetDungeon();
            $sourceId = $this->createSourceDungeon();
            // The Classic Ruins only has a floor at index 1
            $sourceFloor = $this->createSourceFloor($sourceId, 2);

            // Act
            $this->runMigration();

            // Assert
            $this->assertSame($target->id, $sourceFloor->fresh()->dungeon_id);
        } finally {
            DB::rollBack();
        }
    }

    #[Test]
    public function up_givenSourceDungeon_copiesGameIdsAndViewsOntoTargetAndDeletesSource(): void
    {
        DB::beginTransaction();

        try {
            // Arrange
            $target   = $this->getTargetDungeon();
            $sourceId = $this->createSourceDungeon();

            // Act
            $this->runMigration();

            // Assert
            $mergedTarget = DB::table('dungeons')->where('id', $target->id)->first();
            $this->assertSame(self::SOURCE_ZONE_ID, $mergedTarget->zone_id);
            $this->assertSame(self::SOURCE_MAP_ID, $mergedTarget->map_id);
            $this->assertSame(self::SOURCE_INSTANCE_ID, $mergedTarget->instance_id);
            $this->assertSame($target->views + self::SOURCE_VIEWS, $mergedTarget->views);
            $this->assertFalse(DB::table('dungeons')->where('id', $sourceId)->exists());
        } finally {
            DB::rollBack();
        }
    }

    #[Test]
    public function up_givenSpellsOnSourceDungeon_movesThemWithoutDuplicatingOnesTheTargetHas(): void
    {
        DB::beginTransaction();

        try {
            // Arrange
            $target   = $this->getTargetDungeon();
            $sourceId = $this->createSourceDungeon();
            DB::table('spell_dungeons')->insert([
                ['spell_id' => self::SPELL_ID_ON_BOTH, 'dungeon_id' => $target->id],
                ['spell_id' => self::SPELL_ID_ON_BOTH, 'dungeon_id' => $sourceId],
                ['spell_id' => self::SPELL_ID_ON_SOURCE_ONLY, 'dungeon_id' => $sourceId],
            ]);

            // Act
            $this->runMigration();

            // Assert
            $this->assertSame(
                [self::SPELL_ID_ON_BOTH, self::SPELL_ID_ON_SOURCE_ONLY],
                DB::table('spell_dungeons')
                    ->where('dungeon_id', $target->id)
                    ->whereIn('spell_id', [self::SPELL_ID_ON_BOTH, self::SPELL_ID_ON_SOURCE_ONLY])
                    ->orderBy('spell_id')
                    ->pluck('spell_id')
                    ->all(),
            );
            $this->assertFalse(DB::table('spell_dungeons')->where('dungeon_id', $sourceId)->exists());
        } finally {
            DB::rollBack();
        }
    }

    #[Test]
    public function up_givenPageViewAndUserContextOnSourceDungeon_movesThemToTarget(): void
    {
        DB::beginTransaction();

        try {
            // Arrange
            $target   = $this->getTargetDungeon();
            $sourceId = $this->createSourceDungeon();
            $user     = User::factory()->create();
            DB::table('users')->where('id', $user->id)->update(['dungeon_id' => $sourceId]);
            $pageViewId = DB::table('page_views')->insertGetId([
                'user_id'     => $user->id,
                'model_id'    => $sourceId,
                'model_class' => Dungeon::class,
                'session_id'  => 'merge-sod-ahnqiraj-test',
                'source'      => Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON,
            ]);

            // Act
            $this->runMigration();

            // Assert
            $this->assertSame($target->id, DB::table('users')->where('id', $user->id)->value('dungeon_id'));
            $this->assertSame($target->id, DB::table('page_views')->where('id', $pageViewId)->value('model_id'));
        } finally {
            DB::rollBack();
        }
    }

    #[Test]
    public function up_givenNpcOnBothDungeons_keepsOneRowOnTarget(): void
    {
        DB::beginTransaction();

        try {
            // Arrange
            $target   = $this->getTargetDungeon();
            $sourceId = $this->createSourceDungeon();
            DB::table('npc_dungeons')->insert([
                ['npc_id' => self::NPC_ID_ON_BOTH, 'dungeon_id' => $target->id],
                ['npc_id' => self::NPC_ID_ON_BOTH, 'dungeon_id' => $sourceId],
            ]);

            // Act
            $this->runMigration();

            // Assert
            $this->assertSame(1, DB::table('npc_dungeons')->where('npc_id', self::NPC_ID_ON_BOTH)->count());
            $this->assertTrue(
                DB::table('npc_dungeons')->where('npc_id', self::NPC_ID_ON_BOTH)->where('dungeon_id', $target->id)->exists(),
            );
        } finally {
            DB::rollBack();
        }
    }

    private function runMigration(): void
    {
        $migration = require database_path(self::MIGRATION);
        $migration->up();
    }

    private function getTargetDungeon(): object
    {
        return DB::table('dungeons')->where('key', self::TARGET_KEY)->first()
            ?? $this->fail(sprintf('The seeded database must hold %s.', self::TARGET_KEY));
    }

    private function getTargetFloor(object $target): Floor
    {
        return Floor::query()->where('dungeon_id', $target->id)->where('index', 1)->firstOrFail();
    }

    private function createSourceDungeon(): int
    {
        $target = $this->getTargetDungeon();

        return DB::table('dungeons')->insertGetId([
            'expansion_id' => $target->expansion_id,
            'zone_id'      => self::SOURCE_ZONE_ID,
            'map_id'       => self::SOURCE_MAP_ID,
            'instance_id'  => self::SOURCE_INSTANCE_ID,
            'raid'         => 1,
            'key'          => self::SOURCE_KEY,
            'name'         => 'dungeons.classic.ruins_of_ahnqiraj_sod.name',
            'slug'         => 'ruins-of-ahnqiraj-sod',
            'views'        => self::SOURCE_VIEWS,
            'active'       => 1,
        ]);
    }

    private function createSourceFloor(int $sourceDungeonId, int $index): Floor
    {
        return FloorFactory::new()->create([
            'dungeon_id' => $sourceDungeonId,
            'index'      => $index,
            'name'       => 'dungeons.classic.ruins_of_ahnqiraj_sod.floors.ruins_of_ahnqiraj',
            'ui_map_id'  => self::SOURCE_UI_MAP_ID,
        ]);
    }

    private function createSourceMappingVersion(int $sourceDungeonId): MappingVersion
    {
        return MappingVersion::create([
            'game_version_id'                 => GameVersion::query()->where('key', GameVersion::GAME_VERSION_SOD)->value('id'),
            'dungeon_id'                      => $sourceDungeonId,
            'version'                         => 1,
            'enemy_forces_required'           => 0,
            'enemy_forces_required_teeming'   => null,
            'enemy_forces_shrouded'           => 0,
            'enemy_forces_shrouded_zul_gamux' => 0,
            'timer_max_seconds'               => 0,
            'facade_enabled'                  => false,
            'mdt_mapping_hash'                => null,
            'mdt_changes_pending'             => false,
        ]);
    }
}
