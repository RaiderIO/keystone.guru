<?php

namespace Database\Factories\WalkGraph;

use App\Models\DungeonFloorSwitchMarker;
use App\Models\EnemyPack;
use App\Models\WalkGraph\EnemyPackFloorSwitchWalkDistance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnemyPackFloorSwitchWalkDistance>
 */
class EnemyPackFloorSwitchWalkDistanceFactory extends Factory
{
    protected $model = EnemyPackFloorSwitchWalkDistance::class;

    public function definition(): array
    {
        /** @var DungeonFloorSwitchMarker $dungeonFloorSwitchMarker */
        $dungeonFloorSwitchMarker = DungeonFloorSwitchMarker::query()->inRandomOrder()->firstOrFail();

        /** @var EnemyPack $enemyPack */
        $enemyPack = EnemyPack::query()
            ->where('mapping_version_id', $dungeonFloorSwitchMarker->mapping_version_id)
            ->where('floor_id', $dungeonFloorSwitchMarker->floor_id)
            ->inRandomOrder()
            ->firstOrFail();

        return [
            'mapping_version_id'             => $dungeonFloorSwitchMarker->mapping_version_id,
            'floor_id'                       => $dungeonFloorSwitchMarker->floor_id,
            'enemy_pack_id'                  => $enemyPack->id,
            'dungeon_floor_switch_marker_id' => $dungeonFloorSwitchMarker->id,
            'straight_distance'              => 40.0,
            'walk_distance'                  => 50.0,
        ];
    }
}
