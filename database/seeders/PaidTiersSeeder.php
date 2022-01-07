<?php

namespace Database\Seeders;

use App\Models\PaidTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

        foreach (PaidTier::ALL as $paidTierName => $id) {
            PaidTier::create([
                'id'   => $id,
                'name' => $paidTierName,
            ]);
        }
    }

    private function _rollback()
    {
        DB::table('paid_tiers')->truncate();
    }
}
