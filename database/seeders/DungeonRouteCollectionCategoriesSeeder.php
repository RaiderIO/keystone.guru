<?php

namespace Database\Seeders;

use App\Models\DungeonRoute\DungeonRouteCollectionCategory;
use Illuminate\Database\Seeder;

class DungeonRouteCollectionCategoriesSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryAttributes = [];
        foreach (DungeonRouteCollectionCategory::ALL as $categoryName => $id) {
            $categoryAttributes[] = [
                'id'   => $id,
                'name' => $categoryName,
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
