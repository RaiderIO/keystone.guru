<?php

namespace Database\Factories\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Faction;
use App\Models\PublishedState;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Random\RandomException;

class DungeonRouteFactory extends Factory
{
    protected $model = DungeonRoute::class;

    /**
     * Define the model's default state.
     * @throws RandomException
     */
    public function definition(): array
    {
        /** @var SeasonServiceInterface $seasonService */
        $seasonService = app()->make(SeasonServiceInterface::class);

        /** @var Dungeon $dungeon */
        $count    = 0;
        $maxCount = 10;
        do {
            // Prevent infinite loops
            if ($count >= $maxCount) {
                throw new \Exception('Unable to find a dungeon to create a route for!');
            }

            $dungeon = Dungeon::whereNotNull('challenge_mode_id')->inRandomOrder()->first();
            $count++;
        } while ($dungeon->getCurrentMappingVersion() === null);

        $activeSeason = $dungeon->getActiveSeason($seasonService);

        return [
            'public_key'         => DungeonRoute::generateRandomPublicKey(),
            'author_id'          => 1,
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
            'season_id'          => $activeSeason?->id,
            'faction_id'         => Faction::ALL[Faction::FACTION_UNSPECIFIED],
            'team_id'            => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],

            'clone_of'                   => null,
            'title'                      => $this->faker->sentence(),
            'description'                => $this->faker->paragraph(),
            'level_min'                  => $activeSeason?->key_level_min ?? config('keystoneguru.keystone.levels.default_min'),
            'level_max'                  => $activeSeason?->key_level_max ?? config('keystoneguru.keystone.levels.default_max'),
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
