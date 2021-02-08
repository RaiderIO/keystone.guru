<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RouteAttributesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();

        $this->command->info('Adding route attributes');

        $routeAttributesData = [
            [
                'name' => 'rogue-shroud-skip',
                'description' => 'Rogue Shroud skip',
                'category' => 'class'
            ], [
                'name' => 'warlock-gate-skip',
                'description' => 'Warlock Gate skip',
                'category' => 'class'
            ], [
                'name' => 'mage-slow-fall-skip',
                'description' => 'Mage Slow Fall skip',
                'category' => 'class'
            ], [
                'name' => 'invisibility-potion',
                'description' => 'Invisibility Potion',
                'category' => 'item'
            ], [
                'name' => 'death-skip',
                'description' => 'Death skip',
                'category' => 'misc'
            ]
        ];

        // Based on above data, insert into the database
        foreach ($routeAttributesData as $attributeData) {
            $attribute = new \App\Models\RouteAttribute();
            $attribute->category = $attributeData['category'];
            $attribute->name = $attributeData['name'];
            $attribute->description = $attributeData['description'];
            $attribute->save();
        }
    }

    private function _rollback()
    {
        DB::table('route_attributes')->truncate();
    }
}
