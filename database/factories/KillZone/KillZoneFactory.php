<?php

namespace Database\Factories\KillZone;

use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Service\Coordinates\CoordinatesService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Model>
 */
class KillZoneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $floorId = $this->faker->randomElement([
            null,
            1,
            2,
            3,
        ]);

        return [
            'dungeon_route_id' => 1,
            'floor_id'         => $floorId,
            'color'            => $this->faker->hexColor(),
            'description'      => $this->faker->paragraph(),
            'index'            => $this->faker->numberBetween(1, 100),
            'lat'              => $floorId === null ? null : $this->faker->randomFloat(2, CoordinatesService::MAP_MAX_LAT, 0),
            'lng'              => $floorId === null ? null : $this->faker->randomFloat(2, 0, CoordinatesService::MAP_MAX_LNG),
            'created_at'       => Carbon::now(),
            'updated_at'       => Carbon::now(),
        ];
    }

    public function withEnemies(Enemy ...$enemies): self
    {
        return $this->afterCreating(static function (KillZone $killZone) use ($enemies): void {
            foreach ($enemies as $enemy) {
                KillZoneEnemy::factory()
                    ->forEnemy($enemy)
                    ->create(['kill_zone_id' => $killZone->id]);
            }
        });
    }
}
