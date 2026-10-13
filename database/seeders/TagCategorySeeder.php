<?php

namespace Database\Seeders;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\TagCategory;
use Illuminate\Database\Seeder;

class TagCategorySeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tagCategories = [
            TagCategory::DUNGEON_ROUTE_PERSONAL => DungeonRoute::class,
            TagCategory::DUNGEON_ROUTE_TEAM     => DungeonRoute::class,
        ];

        $tagCategoryAttributes = [];
        foreach ($tagCategories as $tagCategoryKey => $class) {
            $tagCategoryAttributes[] = [
                'key'         => $tagCategoryKey,
                'name'        => $tagCategoryKey,
                'model_class' => $class,
            ];
        }

        TagCategory::from(DatabaseSeeder::getTempTableName(TagCategory::class))->insert($tagCategoryAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [TagCategory::class];
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
