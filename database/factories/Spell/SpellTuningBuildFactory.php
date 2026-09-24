<?php

namespace Database\Factories\Spell;

use App\Models\GameVersion\GameVersion;
use App\Models\Spell\SpellTuningBuild;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpellTuningBuild>
 */
class SpellTuningBuildFactory extends Factory
{
    /**
     * Define the model's default state: a retail build compared with the one before it.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_version_id'      => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'from_build'           => '12.1.0.69382',
            'to_build'             => '12.1.0.69404',
            'to_build_number'      => 69404,
            'to_build_released_at' => null,
        ];
    }
}
