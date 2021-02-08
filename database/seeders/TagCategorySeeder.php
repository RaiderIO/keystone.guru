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
            new TagCategory([
                'name'    => TagCategory::DUNGEON_ROUTE_PERSONAL,
                'model_class' => DungeonRoute::class,
            ]),
            new TagCategory([
                'name'    => TagCategory::DUNGEON_ROUTE_TEAM,
                'model_class' => DungeonRoute::class,
            ]),
        ];

        foreach ($tagCategories as $tagCategory) {
            $tagCategory->save();
        }
    }


    private function _rollback()
    {
        DB::table('tag_categories')->truncate();
    }
}
