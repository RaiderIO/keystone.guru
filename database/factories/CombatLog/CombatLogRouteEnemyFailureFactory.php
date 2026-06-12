<?php

namespace Database\Factories\CombatLog;

use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CombatLogRouteEnemyFailure>
 */
class CombatLogRouteEnemyFailureFactory extends Factory
{
    protected $model = CombatLogRouteEnemyFailure::class;

    public function definition(): array
    {
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::inRandomOrder()->first();

        /** @var Floor $floor */
        $floor = $dungeon->floors()->where('facade', 0)->first();

        /** @var MappingVersion $mappingVersion */
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        return [
            'dungeon_id'         => $dungeon->id,
            'floor_id'           => $floor->id,
            'mapping_version_id' => $mappingVersion->id,
            'npc_id'             => null,
            'lat'                => $this->faker->randomFloat(4, 0, 100),
            'lng'                => $this->faker->randomFloat(4, 0, 100),
        ];
    }

    public function withNpc(int $npcId): self
    {
        return $this->state(['npc_id' => $npcId]);
    }
}
