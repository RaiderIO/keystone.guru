<?php

namespace Database\Factories\Npc;

use App\Models\Npc\OpenWorldNpc;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OpenWorldNpc>
 */
class OpenWorldNpcFactory extends Factory
{
    protected $model = OpenWorldNpc::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'id'                    => $this->faker->unique()->numberBetween(950_000_000, 999_999_999),
            'name'                  => $this->faker->name(),
            'display_id'            => $this->faker->numberBetween(1, 150_000),
            'has_portrait'          => false,
            'portrait_unrenderable' => false,
        ];
    }

    public function withoutDisplayId(): self
    {
        return $this->state(fn(array $attributes) => [
            'display_id' => null,
        ]);
    }

    public function withPortrait(): self
    {
        return $this->state(fn(array $attributes) => [
            'has_portrait' => true,
        ]);
    }

    public function withUnrenderablePortrait(): self
    {
        return $this->state(fn(array $attributes) => [
            'has_portrait'          => false,
            'portrait_unrenderable' => true,
        ]);
    }
}
