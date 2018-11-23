<?php

use Illuminate\Database\Seeder;

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

        $this->command->info('Adding route options');

        $routeAttributesData = [
            [
                'name' => 'Rogue Shroud skip',
                'category' => 'class'
            ], [
                'name' => 'Warlock Gate skip',
                'category' => 'class'
            ], [
                'name' => 'Mage Slow Fall skip',
                'category' => 'class'
            ], [
                'name' => 'Invisibility Potion',
                'category' => 'item'
            ], [
                'name' => 'Death skip',
                'category' => 'misc'
            ]
        ];

        // Based on above data, insert into the database
        foreach ($routeAttributesData as $attributeData) {
            $attribute = new \App\Models\RouteAttribute();
            $attribute->name = $attributeData['name'];
            $attribute->category = $attributeData['category'];
            $attribute->save();
        }
    }

    private function _rollback()
    {
        DB::table('route_attributes')->truncate();
    }
}
