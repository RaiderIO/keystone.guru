<?php

namespace Database\Factories\CombatLog;

use App\Models\CombatLog\CombatLogParseFailure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CombatLogParseFailure>
 */
class CombatLogParseFailureFactory extends Factory
{
    protected $model = CombatLogParseFailure::class;

    public function definition(): array
    {
        return [
            'run_id'             => $this->faker->numberBetween(1, 99999999),
            'season_id'          => null,
            'combat_log_version' => 22012000005,
            'line_number'        => $this->faker->numberBetween(1, 500000),
            'raw_line'           => 'SPELL_DAMAGE,Player-1084-0B4087DE,"Pa',
            'message'            => 'Unbalanced quotes in string SPELL_DAMAGE,Player-1084-0B4087DE,"Pa',
            'exception_class'    => 'InvalidArgumentException',
            'resolved_at'        => null,
        ];
    }

    public function resolved(): self
    {
        return $this->state(['resolved_at' => now()]);
    }
}
