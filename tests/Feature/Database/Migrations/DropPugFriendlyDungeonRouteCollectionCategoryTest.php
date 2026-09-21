<?php

namespace Tests\Feature\Database\Migrations;

use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\DungeonRoute\DungeonRouteCollectionCategoryType;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The migration sweeps every PUG friendly collection, not just the fixtures, and deletes a seeded
 * row, so each test runs inside a transaction that is always rolled back.
 */
#[Group('Migrations')]
final class DropPugFriendlyDungeonRouteCollectionCategoryTest extends PublicTestCase
{
    private const MIGRATION = 'migrations/2026_09_21_120000_drop_pug_friendly_dungeon_route_collection_category.php';

    private const PUG_FRIENDLY_CATEGORY_ID = 1;

    #[Test]
    public function up_givenAPugFriendlyCollection_clearsItsCategoryButKeepsTheOthers(): void
    {
        // Arrange
        DB::beginTransaction();

        try {
            $pugFriendly = DungeonRouteCollection::factory()->create([
                'dungeon_route_collection_category_id' => self::PUG_FRIENDLY_CATEGORY_ID,
            ]);
            $expert = DungeonRouteCollection::factory()->create([
                'dungeon_route_collection_category_id' => DungeonRouteCollectionCategoryType::Expert->id(),
            ]);
            $uncategorised = DungeonRouteCollection::factory()->create([
                'dungeon_route_collection_category_id' => null,
            ]);

            // Act
            $migration = require database_path(self::MIGRATION);
            $migration->up();

            // Assert
            $this->assertNull($pugFriendly->refresh()->dungeon_route_collection_category_id);
            $this->assertSame(
                DungeonRouteCollectionCategoryType::Expert->id(),
                $expert->refresh()->dungeon_route_collection_category_id,
            );
            $this->assertNull($uncategorised->refresh()->dungeon_route_collection_category_id);
            $this->assertSame(
                0,
                DungeonRouteCollection::query()
                    ->where('dungeon_route_collection_category_id', self::PUG_FRIENDLY_CATEGORY_ID)
                    ->count(),
            );
        } finally {
            DB::rollBack();
        }
    }

    #[Test]
    public function up_givenASeededPugFriendlyCategory_deletesItButKeepsTheDifficulties(): void
    {
        // Arrange
        DB::beginTransaction();

        try {
            DB::table('dungeon_route_collection_categories')->insertOrIgnore([
                'id'   => self::PUG_FRIENDLY_CATEGORY_ID,
                'name' => 'pug_friendly',
            ]);

            // Act
            $migration = require database_path(self::MIGRATION);
            $migration->up();

            // Assert
            $this->assertFalse(
                DungeonRouteCollectionCategory::query()->whereKey(self::PUG_FRIENDLY_CATEGORY_ID)->exists(),
            );
            $this->assertSame(
                count(DungeonRouteCollectionCategoryType::cases()),
                DungeonRouteCollectionCategory::query()->count(),
            );
        } finally {
            DB::rollBack();
        }
    }

    #[Test]
    public function down_givenTheCategoryIsGone_restoresTheSeededRow(): void
    {
        // Arrange
        DB::beginTransaction();

        try {
            $migration = require database_path(self::MIGRATION);
            $migration->up();

            // Act
            $migration->down();

            // Assert
            $this->assertSame(
                'pug_friendly',
                DungeonRouteCollectionCategory::query()->whereKey(self::PUG_FRIENDLY_CATEGORY_ID)->value('name'),
            );
        } finally {
            DB::rollBack();
        }
    }
}
