<?php

namespace Database\Factories\WalkGraph;

use App\Models\EnemyPack;
use App\Models\WalkGraph\EnemyPackWalkDistance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnemyPackWalkDistance>
 */
class EnemyPackWalkDistanceFactory extends Factory
{
    protected $model = EnemyPackWalkDistance::class;

    public function definition(): array
    {
        /** @var EnemyPack $enemyPack */
        $enemyPack = EnemyPack::query()->inRandomOrder()->firstOrFail();

        return [
            'mapping_version_id'  => $enemyPack->mapping_version_id,
            'floor_id'            => $enemyPack->floor_id,
            'enemy_pack_id'       => $enemyPack->id,
            'other_enemy_pack_id' => $enemyPack->id + 1,
            'straight_distance'   => 40.0,
            'walk_distance'       => 50.0,
        ];
    }
}
