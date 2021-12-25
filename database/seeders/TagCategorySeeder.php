<?php

namespace Database\Seeders;

use App\Models\DungeonRoute;
use App\Models\Tags\TagCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();

        $this->command->info('Adding Tag Categories');

        $tagCategories = [
            TagCategory::DUNGEON_ROUTE_PERSONAL => DungeonRoute::class,
            TagCategory::DUNGEON_ROUTE_TEAM     => DungeonRoute::class,
        ];

        foreach ($tagCategories as $tagCategory => $class) {
            TagCategory::create([
                'id'          => TagCategory::ALL[$tagCategory],
                'name'        => $tagCategory,
                'model_class' => $class,
            ]);
        }
    }


    private function _rollback()
    {
        DB::table('tag_categories')->truncate();
    }
}
