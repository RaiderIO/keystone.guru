<?php

namespace Database\Factories\Spell;

use App\Models\CharacterClassSpecialization;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellSpecialization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpellSpecialization>
 */
class SpellSpecializationFactory extends Factory
{
    /**
     * Define the model's default state: a seeded spell linked to the first seeded specialization.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'spell_id'                          => Spell::query()->orderBy('id')->value('id'),
            'character_class_specialization_id' => CharacterClassSpecialization::query()->orderBy('id')->value('id'),
        ];
    }
}
