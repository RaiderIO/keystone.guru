<?php

namespace Database\Seeders;

use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use App\Models\DungeonRoute\DungeonRouteCollectionCategoryType;
use Illuminate\Database\Seeder;

class DungeonRouteCollectionCategoriesSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryAttributes = [];
        foreach (DungeonRouteCollectionCategoryType::cases() as $categoryType) {
            $categoryAttributes[] = [
                'id'   => $categoryType->id(),
                'name' => $categoryType->value,
            ];
        }

        DungeonRouteCollectionCategory::from(DatabaseSeeder::getTempTableName(DungeonRouteCollectionCategory::class))
            ->insert($categoryAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [DungeonRouteCollectionCategory::class];
    }

    /**
     * @return array<int, string>|null
     */
    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
