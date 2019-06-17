<?php

use Illuminate\Database\Seeder;

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

        $seasons = [
            new App\Models\Season([
                'seasonal_affix_id' => 6,
                'start' => '2018-09-04 00:00:00'
            ]), new App\Models\Season([
                'seasonal_affix_id' => 16,
                'start' => '2019-01-23 00:00:00'
            ])
//            , new App\Models\Season([
//                'seasonal_affix_id' => 10,
//                'start' => '2019-10-19 00:00:00'
//            ])
        ];


        foreach ($seasons as $season) {
            /** @var $race \Illuminate\Database\Eloquent\Model */
            $season->save();
        }
    }

    private function _rollback()
    {
        DB::table('seasons')->truncate();
    }
}
