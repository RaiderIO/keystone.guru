<?php

namespace Database\Seeders;

use App\Models\Expansion;
use App\Models\File;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpansionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();

        $this->command->info('Adding known Expansions');

        $expansions = [
            'expansions.legion.name'                 => new Expansion([
                'active'      => 1,
                'shortname'   => Expansion::EXPANSION_LEGION,
                'color'       => '#27ff0f',
                'released_at' => Carbon::make('2016-08-30 00:00:00'),
            ]), 'expansions.battle_for_azeroth.name' => new Expansion([
                'active'      => 0,
                'shortname'   => Expansion::EXPANSION_BFA,
                'color'       => '#906554',
                'released_at' => Carbon::make('2018-08-14 00:00:00'),
            ]), 'expansions.shadowlands.name'        => new Expansion([
                'active'      => 1,
                'shortname'   => Expansion::EXPANSION_SHADOWLANDS,
                'color'       => '#5832a8',
                'released_at' => Carbon::make('2020-11-24 00:00:00'),
            ]),
        ];


        foreach ($expansions as $name => $expansion) {
            $expansion->name = $name;
            // Temp file
            $expansion->icon_file_id = -1;
            $expansion->save();

            $icon              = new File();
            $icon->model_id    = $expansion->id;
            $icon->model_class = get_class($expansion);
            $icon->disk        = 'public';
            $icon->path        = sprintf('images/expansions/%s.png', $expansion->shortname);
            $icon->save();

            $expansion->icon_file_id = $icon->id;
            $expansion->save();
        }
    }

    private function _rollback()
    {
        DB::table('expansions')->truncate();
        DB::table('files')->where('model_class', 'App\Models\Expansion')->delete();
    }
}
