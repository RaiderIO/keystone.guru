<?php

namespace Database\Factories\WalkGraph;

use App\Models\DungeonFloorSwitchMarker;
use App\Models\WalkGraph\FloorSwitchMarkerWalkDistance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FloorSwitchMarkerWalkDistance>
 */
class FloorSwitchMarkerWalkDistanceFactory extends Factory
{
    protected $model = FloorSwitchMarkerWalkDistance::class;

    public function definition(): array
    {
        /** @var DungeonFloorSwitchMarker $dungeonFloorSwitchMarker */
        $dungeonFloorSwitchMarker = DungeonFloorSwitchMarker::query()->inRandomOrder()->firstOrFail();

        return [
            'mapping_version_id'                   => $dungeonFloorSwitchMarker->mapping_version_id,
            'floor_id'                             => $dungeonFloorSwitchMarker->floor_id,
            'dungeon_floor_switch_marker_id'       => $dungeonFloorSwitchMarker->id,
            'other_dungeon_floor_switch_marker_id' => $dungeonFloorSwitchMarker->id + 1,
            'straight_distance'                    => 40.0,
            'walk_distance'                        => 50.0,
        ];
    }
}
