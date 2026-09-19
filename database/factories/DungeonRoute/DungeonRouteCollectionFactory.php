<?php

namespace Database\Factories\DungeonRoute;

use App\Models\DungeonRoute\DungeonRouteCollection;
use App\Models\GameVersion\GameVersion;
use App\Models\PublishedState;
use App\Models\Season;
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
            'user_id'                              => 1,
            'team_id'                              => null,
            'dungeon_route_collection_category_id' => null,
            'game_version_id'                      => GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'season_id'                            => null,
            'public_key'                           => DungeonRouteCollection::generateRandomPublicKey(),
            'published_state_id'                   => PublishedState::ALL[PublishedState::WORLD],
            'name'                                 => $this->faker->sentence(3),
            'description'                          => $this->faker->paragraph(),
            'created_at'                           => Carbon::now(),
            'updated_at'                           => Carbon::now(),
        ];
    }

    /**
     * A free-form collection of the passed game version.
     */
    public function freeForm(GameVersion $gameVersion): static
    {
        return $this->state([
            'game_version_id' => $gameVersion->id,
            'season_id'       => null,
        ]);
    }

    /**
     * A season set: bound to the game version whose expansion the season belongs to (retail by default).
     */
    public function seasonSet(Season $season, ?GameVersion $gameVersion = null): static
    {
        return $this->state([
            'game_version_id' => $gameVersion !== null ? $gameVersion->id : GameVersion::ALL[GameVersion::GAME_VERSION_RETAIL],
            'season_id'       => $season->id,
        ]);
    }
}
