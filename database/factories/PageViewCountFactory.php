<?php

namespace Database\Factories;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PageViewCount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PageViewCount>
 */
class PageViewCountFactory extends Factory
{
    protected $model = PageViewCount::class;

    /**
     * Define the model's default state: yesterday's regular views of a route.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'model_class' => DungeonRoute::class,
            'model_id'    => $this->faker->numberBetween(1, 1000000),
            'source'      => DungeonRoute::PAGE_VIEW_SOURCE_VIEW_ROUTE,
            'viewed_on'   => now()->subDay()->toDateString(),
            'views'       => $this->faker->numberBetween(1, 1000),
        ];
    }
}
