<?php

namespace Database\Seeders;

use App\Models\Patreon\PatreonBenefit;
use Illuminate\Database\Seeder;

class PatreonBenefitsSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->rollback();

        $this->command->info('Adding Patreon Benefits');

        $patreonBenefitAttributes = [];
        foreach (PatreonBenefit::ALL as $patreonBenefitKey => $id) {
            $patreonBenefitAttributes[] = [
                'id'   => $id,
                'name' => sprintf('patreonbenefits.%s', $patreonBenefitKey),
                'key'  => $patreonBenefitKey,
            ];
        }

        PatreonBenefit::insert($patreonBenefitAttributes);
    }

    private function rollback()
    {
        PatreonBenefit::truncate();
    }

    public static function getAffectedModelClasses(): array
    {
        return [PatreonBenefit::class];
    }
}
