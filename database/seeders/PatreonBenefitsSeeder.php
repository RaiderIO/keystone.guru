<?php

namespace Database\Seeders;

use App\Models\Patreon\PatreonBenefit;
use Illuminate\Database\Seeder;

class PatreonBenefitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->rollback();

        $this->command->info('Adding Patreon Benefits');

        foreach (PatreonBenefit::ALL as $patreonBenefitKey => $id) {
            PatreonBenefit::create([
                'id'   => $id,
                'name' => sprintf('patreonbenefits.%s', $patreonBenefitKey),
                'key'  => $patreonBenefitKey,
            ]);
        }
    }

    private function rollback()
    {
        PatreonBenefit::truncate();
    }
}
