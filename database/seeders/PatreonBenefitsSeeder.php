<?php

namespace Database\Seeders;

use App\Models\Patreon\PatreonBenefit;
use Illuminate\Database\Seeder;

class PatreonBenefitsSeeder extends Seeder implements TableSeederInterface
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patreonBenefitAttributes = [];
        foreach (PatreonBenefit::ALL as $patreonBenefitKey => $id) {
            $patreonBenefitAttributes[] = [
                'id'   => $id,
                'name' => sprintf('patreonbenefits.%s', $patreonBenefitKey),
                'key'  => $patreonBenefitKey,
            ];
        }

        PatreonBenefit::from(DatabaseSeeder::getTempTableName(PatreonBenefit::class))->insert($patreonBenefitAttributes);
    }

    public static function getAffectedModelClasses(): array
    {
        return [PatreonBenefit::class];
    }

    public static function getAffectedEnvironments(): ?array
    {
        // All environments
        return null;
    }
}
