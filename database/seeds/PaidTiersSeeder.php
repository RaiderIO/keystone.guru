<?php

use Illuminate\Database\Seeder;
use App\Models\File;

class PaidTiersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->_rollback();

        $this->command->info('Adding Paid Tiers');

        $paidTiers = [
            new \App\Models\PaidTier(['name' => 'ad-free'])
        ];

        foreach ($paidTiers as $paidTier) {
            $paidTier->save();
        }
    }

    private function _rollback()
    {
        DB::table('paid_tiers')->truncate();
    }
}
