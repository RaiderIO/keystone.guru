<?php

namespace Database\Factories\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyResolution;
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
        // Any enemy of any dungeon's current mapping version - not every dungeon has enemies on its first floor,
        // so the enemy is what the row is built around rather than a randomly picked dungeon
        /** @var Enemy $enemy */
        $enemy = Enemy::query()
            ->whereNotNull('npc_id')
            ->whereIn('mapping_version_id', MappingVersion::query()->selectRaw('MAX(id)')->groupBy('dungeon_id'))
            ->whereIn('floor_id', Floor::query()->where('facade', 0)->select('id'))
            ->inRandomOrder()
            ->first();

        /** @var Floor $floor */
        $floor = $enemy->floor;

        $distance = $this->faker->randomFloat(3, 40, 150);

        return [
            'dungeon_id'         => $floor->dungeon_id,
            'floor_id'           => $floor->id,
            'mapping_version_id' => $enemy->mapping_version_id,
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
