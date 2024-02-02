<?php

namespace Database\Factories\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class DungeonRouteFactory extends Factory
{
    protected $model = DungeonRoute::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $allDungeons = collect(Dungeon::ALL)->flatten();

        return [
            'public_key'         => DungeonRoute::generateRandomPublicKey(7, 'public_key', false),
            'author_id'          => 1,
            'dungeon_id'         => $allDungeons->search($allDungeons->random(1)),
            'mapping_version_id' => $this->faker->numberBetween(),
            'faction_id'         => 1,
            'team_id'            => 1,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],

            'clone_of'                    => null,
            'title'                       => $this->faker->title(),
            'description'                 => '',
            'level_min'                   => 2,
            'level_max'                   => 28,
            'difficulty'                  => 'Casual',
            'seasonal_index'              => null,
            'enemy_forces'                => 0,
            'teeming'                     => 0,
            'demo'                        => 0,
            'pull_gradient'               => null,
            'pull_gradient_apply_always'  => false,
            'dungeon_difficulty'          => null,
            'views'                       => 0,
            'views_embed'                 => 0,
            'popularity'                  => 0,
            'rating'                      => 0,
            'rating_count'                => 0,
            'thumbnail_refresh_queued_at' => null,
            'thumbnail_updated_at'        => null,
            'updated_at'                  => null,
            'created_at'                  => Carbon::now(),
            'published_at'                => Carbon::now(),
            'expires_at'                  => null,
        ];
    }
}
