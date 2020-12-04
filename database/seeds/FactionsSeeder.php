<?php

use App\Models\Faction;
use App\Models\File;
use Illuminate\Database\Seeder;

class FactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();
        $this->command->info('Adding known factions');

        $factions = [
            new Faction(['name' => 'Unspecified', 'icon_file_id' => -1, 'color' =>'gray']),
            new Faction(['name' => 'Horde', 'icon_file_id' => -1, 'color' => 'red']),
            new Faction(['name' => 'Alliance', 'icon_file_id' => -1, 'color' => 'blue'])
        ];

        foreach($factions as $faction){
            /** @var $faction \Illuminate\Database\Eloquent\Model */
            $faction->save();

            $iconName = strtolower(str_replace(' ', '', $faction->name));
            $icon = new File();
            $icon->model_id = $faction->id;
            $icon->model_class = get_class($faction);
            $icon->disk = 'public';
            $icon->path = sprintf('images/factions/%s.png', $iconName);
            $icon->save();

            $faction->icon_file_id = $icon->id;
            $faction->save();
        }
    }

    private function _rollback()
    {
        DB::table('factions')->truncate();
        DB::table('files')->where('model_class', '=', 'App\Models\Faction')->delete();
    }
}
