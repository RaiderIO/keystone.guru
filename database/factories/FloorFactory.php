<?php

namespace Database\Factories;

use App\Models\Floor;
use Illuminate\Database\Eloquent\Factories\Factory;

class FloorFactory extends Factory
{
    protected $model = Floor::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'ingame_min_x' => $minX = $this->faker->numberBetween(100, 10000),
            'ingame_max_x' => $minX + $this->faker->numberBetween(100, 10000),
            'ingame_min_y' => $minY = $this->faker->numberBetween(100, 10000),
            'ingame_max_y' => $minY + $this->faker->numberBetween(100, 10000),
        ];
    }
}
