<?php

namespace Database\Factories\Spell;

use App\Models\Spell\Spell;
use App\Models\Spell\SpellTuningChange;
use App\Models\Spell\SpellTuningChangeType;
use App\Service\Spell\Description\Dtos\SpellDescriptionValueKind;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpellTuningChange>
 */
class SpellTuningChangeFactory extends Factory
{
    /**
     * Define the model's default state: a damage coefficient going 3 -> 4 on a seeded, visible spell.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var Spell $spell */
        $spell = Spell::query()->where('hidden_on_map', false)->orderBy('id')->firstOrFail();

        return [
            'game_version_id' => $spell->game_version_id,
            'spell_id'        => $spell->id,
            'from_build'      => '12.1.0.69382',
            'to_build'        => '12.1.0.69404',
            'to_build_number' => 69404,
            'change_type'     => SpellTuningChangeType::ValueChanged,
            'value_index'     => 0,
            'kind'            => SpellDescriptionValueKind::Damage,
            'old_coefficient' => 3.0,
            'new_coefficient' => 4.0,
            'old_text'        => '29,095',
            'new_text'        => '38,793',
            'delta'           => 4 / 3 - 1,
        ];
    }
}
