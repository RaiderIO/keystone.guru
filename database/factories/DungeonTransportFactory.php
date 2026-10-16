<?php

namespace Database\Factories;

use App\Models\DungeonTransport;
use App\Models\MapIconType;
use App\Service\Coordinates\CoordinatesService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DungeonTransport>
 */
class DungeonTransportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mapping_version_id'          => 1,
            'floor_id'                    => 1,
            'map_icon_type_id'            => MapIconType::ALL[MapIconType::MAP_ICON_TYPE_PORTAL_BLUE],
            'linked_dungeon_transport_id' => null,
            'target_dungeon_id'           => null,
            'link_key'                    => null,
            'path_vertices_json'          => null,
            'lat'                         => $this->faker->randomFloat(2, CoordinatesService::MAP_MAX_LAT, 0),
            'lng'                         => $this->faker->randomFloat(2, 0, CoordinatesService::MAP_MAX_LNG),
            'comment'                     => null,
        ];
    }
}
