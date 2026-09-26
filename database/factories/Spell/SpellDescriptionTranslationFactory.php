<?php

namespace Database\Factories\Spell;

use App\Models\Spell\Spell;
use App\Models\Spell\SpellDescriptionTranslation;
use App\Service\Spell\Description\Dtos\SpellDescriptionValueKind;
use App\Service\WagoTools\GameLocale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpellDescriptionTranslation>
 */
class SpellDescriptionTranslationFactory extends Factory
{
    /**
     * Define the model's default state: the German description of a seeded, visible spell.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var Spell $spell */
        $spell = Spell::query()->where('hidden_on_map', false)->orderBy('id')->firstOrFail();

        return [
            'spell_id'           => $spell->id,
            'locale'             => GameLocale::German->value,
            'description_format' => 'Fügt dem Ziel %1$s Schaden zu und betäubt es %2$s lang.',
            'description_values' => [
                ['kind' => SpellDescriptionValueKind::Damage->value, 'text' => '29,095', 'coefficient' => 3.0],
                ['kind' => SpellDescriptionValueKind::Duration->value, 'text' => '8 Sek.'],
            ],
        ];
    }
}
