<?php

namespace Database\Seeders;

use App\Models\NpcClassification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NpcClassificationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();
        $this->command->info('Adding known Npc Classifications');

        $classificationsData = [
            'Normal'     => ['color' => 'white'],
            'Elite'      => ['color' => 'yellow'],
            'Boss'       => ['color' => 'red'],
            'Final Boss' => ['color' => 'red'],
        ];

        foreach ($classificationsData as $name => $classificationData) {
            $classification        = new NpcClassification();
            $classification->name  = $name;
            $classification->color = $classificationData['color'];
            $classification->save();
        }
    }

    private function _rollback()
    {
        DB::table('npc_classifications')->truncate();
    }
}
