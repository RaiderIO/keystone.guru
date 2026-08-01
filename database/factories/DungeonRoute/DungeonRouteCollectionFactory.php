<?php

namespace Database\Factories\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\PublishedState;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<DungeonRouteCollection>
 */
class DungeonRouteCollectionFactory extends Factory
{
    protected $model = DungeonRouteCollection::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id'            => 1,
            'team_id'            => null,
            'public_key'         => DungeonRouteCollection::generateRandomPublicKey(),
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'name'               => $this->faker->sentence(3),
            'description'        => $this->faker->paragraph(),
            'created_at'         => Carbon::now(),
            'updated_at'         => Carbon::now(),
        ];
    }
}
