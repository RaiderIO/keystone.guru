<?php

namespace Database\Factories\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\Dungeon;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CombatLogRouteEnemyResolution>
 */
class CombatLogRouteEnemyResolutionFactory extends Factory
{
    protected $model = CombatLogRouteEnemyResolution::class;

    public function definition(): array
    {
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::inRandomOrder()->first();

        /** @var Floor $floor */
        $floor = $dungeon->floors()->where('facade', 0)->first();

        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        /** @var Enemy $enemy */
        $enemy = $mappingVersion->enemies()->where('floor_id', $floor->id)->first();

        $distance = $this->faker->randomFloat(3, 40, 150);

        return [
            'dungeon_id'         => $dungeon->id,
            'floor_id'           => $floor->id,
            'mapping_version_id' => $mappingVersion->id,
            'npc_id'             => $enemy->npc_id,
            'enemy_id'           => $enemy->id,
            'lat'                => $this->faker->randomFloat(4, 0, 100),
            'lng'                => $this->faker->randomFloat(4, 0, 100),
            'enemy_lat'          => $enemy->lat,
            'enemy_lng'          => $enemy->lng,
            'distance'           => $distance,
            'weighted_distance'  => $distance,
        ];
    }

    public function withNpc(int $npcId): self
    {
        return $this->state(['npc_id' => $npcId]);
    }

    public function withDistance(float $distance): self
    {
        return $this->state([
            'distance'          => $distance,
            'weighted_distance' => $distance,
        ]);
    }
}
