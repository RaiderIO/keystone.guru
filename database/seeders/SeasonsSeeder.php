<?php

namespace Database\Seeders;

use App\Models\Expansion;
use App\Models\Season;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SeasonsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();

        $this->command->info('Adding known Seasons');

        /** @var Collection|Expansion[] $expansions */
        $expansions = Expansion::all()->mapWithKeys(function (Expansion $expansion) {
            return [$expansion->shortname => $expansion->id];
        });

        $seasons = [
            [
                'expansion_id'      => $expansions->get(Expansion::EXPANSION_BFA),
                'seasonal_affix_id' => 6,
                'start'             => '2018-09-04 00:00:00',
                'presets'           => 3,
            ], [
                'expansion_id'      => $expansions->get(Expansion::EXPANSION_BFA),
                'seasonal_affix_id' => 16,
                'start'             => '2019-01-23 00:00:00',
                'presets'           => 0,
            ], [
                'expansion_id'      => $expansions->get(Expansion::EXPANSION_BFA),
                'seasonal_affix_id' => 17,
                'start'             => '2019-07-10 00:00:00',
                'presets'           => 3,
            ], [
                'expansion_id'      => $expansions->get(Expansion::EXPANSION_BFA),
                'seasonal_affix_id' => 18,
                'start'             => '2020-01-21 00:00:00',
                'presets'           => 0,
            ], [
                'expansion_id'      => $expansions->get(Expansion::EXPANSION_SHADOWLANDS),
                'seasonal_affix_id' => 22,
                'start'             => '2020-12-08 00:00:00',
                'presets'           => 0,
            ], [
                'expansion_id'      => $expansions->get(Expansion::EXPANSION_SHADOWLANDS),
                'seasonal_affix_id' => 23,
                'start'             => '2021-07-06 00:00:00',
                'presets'           => 2,
            ], [
                'expansion_id'      => $expansions->get(Expansion::EXPANSION_LEGION),
                'seasonal_affix_id' => 25,
                'start'             => '2021-12-07 00:00:00',
                'presets'           => 0,
            ],
        ];


        foreach ($seasons as $season) {
            Season::create($season);
        }
    }

    private function _rollback()
    {
        DB::table('seasons')->truncate();
    }
}
