<?php

use App\Models\MapIconType;
use Illuminate\Database\Seeder;

class MapIconTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();
        $this->command->info('Adding known Map Icon Types');

        $mapIconData = [
            'awakened_obelisk' => ['name' => 'Awakened Obelisk', 'admin_only' => true],
            'comment' => ['name' => 'Comment'],
            'door' => ['name' => 'Door', 'admin_only' => true],
            'door_down' => ['name' => 'Door Down', 'admin_only' => true],
            'door_left' => ['name' => 'Door Left', 'admin_only' => true],
            'door_locked' => ['name' => 'Door Locked', 'admin_only' => true],
            'door_right' => ['name' => 'Door Right', 'admin_only' => true],
            'door_up' => ['name' => 'Door Up', 'admin_only' => true],
            'dot_yellow' => ['name' => 'Yellow Dot'],
            'dungeon_start' => ['name' => 'Dungeon Start', 'admin_only' => true],
            'gateway' => ['name' => 'Gateway'],
            'graveyard' => ['name' => 'Graveyard', 'admin_only' => true],
            'greasebot' => ['name' => 'Grease bot (haste)', 'admin_only' => true],
            'shockbot' => ['name' => 'Shock bot (damage)', 'admin_only' => true],
            'warlock_gateway' => ['name' => 'Warlock Gateway'],
            'weldingbot' => ['name' => 'Welding bot (-damage taken/+healing received)', 'admin_only' => true],
        ];

        foreach ($mapIconData as $key => $mapIcon)
        {
            $mapIconType = new MapIconType();
            $mapIconType->key = $key;
            $mapIconType->name = $mapIcon['name'];

            $imageSize = getimagesize(resource_path(sprintf('/assets/images/mapicon/%s.png', $key)));
            $mapIconType->width = $imageSize[0];
            $mapIconType->height = $imageSize[1];
            $mapIconType->admin_only = isset($mapIcon['admin_only']) ? $mapIcon['admin_only'] : 0;
            $mapIconType->save();
        }
    }

    private function _rollback()
    {
        DB::table('map_icon_types')->truncate();
    }
}
