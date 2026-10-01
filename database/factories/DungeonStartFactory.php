<?php

namespace Database\Factories;

use App\Models\DungeonStart;
use App\Service\Coordinates\CoordinatesService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DungeonStart>
 */
class DungeonStartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mapping_version_id' => 1,
            'floor_id'           => 1,
            'target_dungeon_id'  => null,
            'lat'                => $this->faker->randomFloat(2, CoordinatesService::MAP_MAX_LAT, 0),
            'lng'                => $this->faker->randomFloat(2, 0, CoordinatesService::MAP_MAX_LNG),
            'comment'            => null,
        ];
    }
}
