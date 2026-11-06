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
        $count = 0;
        do {
            if (++$count > 20) {
                throw new \RuntimeException('Unable to find a dungeon with a non-facade floor and a current mapping version');
            }

            /** @var Dungeon $dungeon */
            $dungeon = Dungeon::inRandomOrder()->first();

            /** @var Floor|null $floor */
            $floor = $dungeon->floors()->where('facade', 0)->first();

            /** @var MappingVersion|null $mappingVersion */
            $mappingVersion = $dungeon->getCurrentMappingVersion();
        } while ($floor === null || $mappingVersion === null);

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
