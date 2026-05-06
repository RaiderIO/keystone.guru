<?php

namespace Database\Factories;

use App\Models\MapIcon;
use App\Models\MapIconType;
use App\Service\Coordinates\CoordinatesService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MapIcon>
 */
class MapIconFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mapping_version_id' => null,
            'floor_id'           => 1,
            'dungeon_route_id'   => 1,
            'team_id'            => null,
            'map_icon_type_id'   => $this->faker->randomElement(MapIconType::ALL),
            'lat'                => $this->faker->randomFloat(2, CoordinatesService::MAP_MAX_LAT, 0),
            'lng'                => $this->faker->randomFloat(2, 0, CoordinatesService::MAP_MAX_LNG),
            'comment'            => $this->faker->sentence(),
            'permanent_tooltip'  => $this->faker->boolean(),
            'seasonal_index'     => 0,
        ];
    }
}
