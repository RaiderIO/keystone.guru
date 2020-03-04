<?php

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
            'Awakened Obelisk' => ['key' => 'awakened_obelisk'],
            'Door' => ['key' => 'door'],
            'Door Down' => ['key' => 'door_down'],
            'Door Left' => ['key' => 'door_left'],
            'Door Locked' => ['key' => 'door_locked'],
            'Door Right' => ['key' => 'door_right'],
            'Door Up' => ['key' => 'door_up'],
            'Yellow Dot' => ['key' => 'dot_yellow'],
            'Dungeon Start' => ['key' => 'dungeon_start'],
            'Gateway' => ['key' => 'gateway'],
            'Graveyard' => ['key' => 'graveyard'],
            'Grease bot (haste)' => ['key' => 'greasebot'],
            'Shock bot (damage)' => ['key' => 'shockbot'],
            'Warlock Gateway' => ['key' => 'warlock_gateway'],
            'Welding bot (-damage taken/+healing received)' => ['key' => 'weldingbot'],
        ];

        foreach ($mapIconData as $name => $mapIcon)
        {
            $classification = new \App\Models\NpcClassification();
            $classification->name = $name;
            $classification->color = $mapIcon['color'];
            $classification->save();
        }
    }

    private function _rollback()
    {
        DB::table('map_icons')->truncate();
    }
}
