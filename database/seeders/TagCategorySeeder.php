<?php

namespace Database\Seeders;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Tags\TagCategory;
use Illuminate\Database\Seeder;

class TagCategorySeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command->info('Adding Tag Categories');

        $tagCategories = [
            TagCategory::DUNGEON_ROUTE_PERSONAL => DungeonRoute::class,
            TagCategory::DUNGEON_ROUTE_TEAM     => DungeonRoute::class,
        ];

        $tagCategoryAttributes = [];
        foreach ($tagCategories as $tagCategory => $class) {
            $tagCategoryAttributes[] = [
                'name'        => $tagCategory,
                'model_class' => $class,
            ];
        }

        TagCategory::from(DatabaseSeeder::getTempTableName(TagCategory::class))->insert($tagCategoryAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [TagCategory::class];
    }
}
