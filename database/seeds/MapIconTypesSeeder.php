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
            'awakened_obelisk' => ['name' => 'Awakened Obelisk'],
            'door' => ['name' => 'Door'],
            'door_down' => ['name' => 'Door Down'],
            'door_left' => ['name' => 'Door Left'],
            'door_locked' => ['name' => 'Door Locked'],
            'door_right' => ['name' => 'Door Right'],
            'door_up' => ['name' => 'Door Up'],
            'dot_yellow' => ['name' => 'Yellow Dot'],
            'dungeon_start' => ['name' => 'Dungeon Start'],
            'gateway' => ['name' => 'Gateway'],
            'graveyard' => ['name' => 'Graveyard'],
            'greasebot' => ['name' => 'Grease bot (haste)'],
            'shockbot' => ['name' => 'Shock bot (damage)'],
            'warlock_gateway' => ['name' => 'Warlock Gateway'],
            'weldingbot' => ['name' => 'Welding bot (-damage taken/+healing received)'],
        ];

        foreach ($mapIconData as $key => $mapIcon)
        {
            $mapIconType = new MapIconType();
            $mapIconType->key = $key;
            $mapIconType->name = $mapIcon['name'];
            $mapIconType->save();
        }
    }

    private function _rollback()
    {
        DB::table('map_icons')->truncate();
    }
}
