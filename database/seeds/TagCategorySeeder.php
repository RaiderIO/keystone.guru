<?php

use App\Models\DungeonRoute;
use Illuminate\Database\Seeder;

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
            new App\Models\Tags\TagCategory([
                'name'    => 'dungeon_route',
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
