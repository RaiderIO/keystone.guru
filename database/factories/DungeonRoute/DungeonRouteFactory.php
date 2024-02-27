<?php

namespace Database\Factories\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Faction;
use App\Models\PublishedState;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class DungeonRouteFactory extends Factory
{
    protected $model = DungeonRoute::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        /** @var Dungeon $dungeon */
        $dungeon = Dungeon::with('currentMappingVersion')->inRandomOrder()->first();

        return [
            'public_key'         => DungeonRoute::generateRandomPublicKey(),
            'author_id'          => 1,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->currentMappingVersion->id,
            'faction_id'         => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            'team_id'            => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],

            'clone_of'                   => null,
            'title'                      => $this->faker->title(),
            'description'                => '',
            'level_min'                  => 2,
            'level_max'                  => 28,
            'difficulty'                 => 'Casual',
            'seasonal_index'             => 0,
            'enemy_forces'               => 0,
            'teeming'                    => 0,
            'demo'                       => 0,
            'pull_gradient'              => '',
            'pull_gradient_apply_always' => 0,
            'dungeon_difficulty'         => null,
            'views'                      => 0,
            'views_embed'                => 0,
            'popularity'                 => 0,
            'rating'                     => 0,
            'rating_count'               => 0,
            'created_at'                 => Carbon::now(),
            'published_at'               => Carbon::now(),
            'expires_at'                 => null,
        ];
    }
}
