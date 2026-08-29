<?php

namespace Database\Factories\CombatLog;

use App\Models\CombatLog\CombatLogSpellPropertyObservation;
use App\Models\CombatLog\SpellProperty;
use App\Models\Spell\Spell;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<CombatLogSpellPropertyObservation>
 */
class CombatLogSpellPropertyObservationFactory extends Factory
{
    protected $model = CombatLogSpellPropertyObservation::class;

    public function definition(): array
    {
        /** @var Spell $spell */
        $spell = Spell::inRandomOrder()->first();

        return [
            'spell_id'        => $spell->id,
            'property'        => SpellProperty::Aura->value,
            'observed_on'     => Carbon::today(),
            'combat_log_path' => sprintf('factory/%s.log', $this->faker->uuid()),
        ];
    }
}
