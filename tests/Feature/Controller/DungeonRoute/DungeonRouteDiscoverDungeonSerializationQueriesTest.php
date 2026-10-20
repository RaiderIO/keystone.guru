<?php

namespace Tests\Feature\Controller\DungeonRoute;

use App\Models\CharacterClassSpecialization;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRoutePlayerSpecialization;
use App\Models\GameVersion\GameVersion;
use App\Models\PublishedState;
use App\Models\Season;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Telescope's request watcher JSON-encodes a view's data after the response is rendered, which evaluates the
 * appended attributes of every route in the discover leaderboard's paginator. These tests do the same encode.
 */
#[Group('Discover')]
final class DungeonRouteDiscoverDungeonSerializationQueriesTest extends PublicTestCase
{
    #[Test]
    public function discoverDungeon_givenMoreRoutesOnThePage_serializingThePaginatorRunsNoExtraQueries(): void
    {
        // Arrange
        [$gameVersion, $season, $dungeon] = $this->getCurrentSeasonDungeon();
        $dungeonRoutes                    = collect();

        try {
            $dungeonRoutes->push($this->createQualifyingRoute($gameVersion, $season, $dungeon));
            $oneRouteResponse = $this->getDiscoverDungeon($gameVersion, $dungeon);

            $dungeonRoutes->push($this->createQualifyingRoute($gameVersion, $season, $dungeon));
            $dungeonRoutes->push($this->createQualifyingRoute($gameVersion, $season, $dungeon));
            $threeRoutesResponse = $this->getDiscoverDungeon($gameVersion, $dungeon);

            // Act
            $oneRouteQueries    = $this->captureQueries(static fn() => json_encode($oneRouteResponse->viewData('paginator')));
            $threeRoutesQueries = $this->captureQueries(static fn() => json_encode($threeRoutesResponse->viewData('paginator')));

            // Assert
            $this->assertSame(
                $oneRouteResponse->viewData('paginator')->count() + 2,
                $threeRoutesResponse->viewData('paginator')->count(),
                'The second render must list the two extra routes, or comparing the query counts proves nothing.',
            );
            $this->assertCount($oneRouteQueries->count(), $threeRoutesQueries, $threeRoutesQueries->implode("\n"));
        } finally {
            $dungeonRoutes->each(static fn(DungeonRoute $dungeonRoute) => $dungeonRoute->delete());
            Cache::store('tmp_file')->flush();
        }
    }

    #[Test]
    public function discoverDungeon_givenRoutesWithPlayerSpecializations_serializesThemWithoutQueryingThePivot(): void
    {
        // Arrange
        [$gameVersion, $season, $dungeon] = $this->getCurrentSeasonDungeon();
        $dungeonRoutes                    = collect();
        $specializations                  = CharacterClassSpecialization::query()->orderBy('id')->limit(2)->get();
        $this->assertCount(2, $specializations, 'Expected the seeded database to hold character class specializations');

        try {
            foreach ($specializations as $specialization) {
                $dungeonRoute = $this->createQualifyingRoute($gameVersion, $season, $dungeon);
                DungeonRoutePlayerSpecialization::create([
                    'dungeon_route_id'                  => $dungeonRoute->id,
                    'character_class_specialization_id' => $specialization->id,
                ]);
                $dungeonRoutes->push($dungeonRoute);
            }
            $response = $this->getDiscoverDungeon($gameVersion, $dungeon);

            // Act
            $serialized = '';
            $queries    = $this->captureQueries(static function () use ($response, &$serialized) {
                $serialized = (string)json_encode($response->viewData('paginator'));
            });

            // Assert
            $this->assertEmpty(
                $queries->filter(static fn(string $sql) => str_contains($sql, 'dungeon_route_player_specializations')),
                $queries->implode("\n"),
            );
            $serializedSpecializationIdsByTitle = $this->getSerializedSpecializationIdsByTitle($serialized);
            foreach ($dungeonRoutes as $index => $dungeonRoute) {
                $this->assertSame([$specializations[$index]->id], $serializedSpecializationIdsByTitle[$dungeonRoute->title] ?? null);
            }
        } finally {
            $dungeonRoutes->each(static fn(DungeonRoute $dungeonRoute) => $dungeonRoute->delete());
            Cache::store('tmp_file')->flush();
        }
    }

    /**
     * @return array<string, array<int, int>> The specialization ids of each serialized route's setup, by route title.
     */
    private function getSerializedSpecializationIdsByTitle(string $serializedPaginator): array
    {
        /** @var array{data: array<int, array{title: string, setup: array{specializations: array<int, array{id: int}>}}>} $paginator */
        $paginator = json_decode($serializedPaginator, true);

        $result = [];
        foreach ($paginator['data'] as $route) {
            $result[$route['title']] = array_column($route['setup']['specializations'], 'id');
        }

        return $result;
    }

    /**
     * @return array{0: GameVersion, 1: Season, 2: Dungeon}
     */
    private function getCurrentSeasonDungeon(): array
    {
        $gameVersion = GameVersion::getDefaultGameVersion();
        $season      = app(SeasonServiceInterface::class)->getCurrentSeason($gameVersion->expansion);

        return [$gameVersion, $season, $season->dungeons()->active()->firstOrFail()];
    }

    /**
     * @return TestResponse<Response>
     */
    private function getDiscoverDungeon(GameVersion $gameVersion, Dungeon $dungeon): TestResponse
    {
        Cache::store('tmp_file')->flush();

        return $this->get(route('dungeonroutes.discoverdungeon', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
        ]))->assertOk();
    }

    /**
     * @return Collection<int, string> The SQL of every query $callback ran.
     */
    private function captureQueries(callable $callback): Collection
    {
        $queries = collect();
        DB::listen(static function (QueryExecuted $query) use ($queries) {
            $queries->push($query->sql);
        });

        $callback();

        // DB::listen cannot be removed again; detaching the collection keeps later captures independent
        return $queries->splice(0);
    }

    /**
     * A published, non-expired route in $season on $dungeon that satisfies the discover leaderboard's filters.
     */
    private function createQualifyingRoute(GameVersion $gameVersion, Season $season, Dungeon $dungeon): DungeonRoute
    {
        $mappingVersion = $dungeon->getCurrentMappingVersionForGameVersion($gameVersion);
        $this->assertNotNull($mappingVersion, 'Expected the current season dungeon to be mapped for the default game version');

        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => $season->id,
            'team_id'            => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'teeming'            => false,
            'enemy_forces'       => $mappingVersion->enemy_forces_required,
            'expires_at'         => null,
            'published_at'       => Carbon::now(),
        ]);
    }
}
