<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
use App\Models\Season;
use App\Models\Team;
use App\Repositories\Database\DungeonRoute\Dtos\WeeklyRoute;
use App\Repositories\Database\DungeonRoute\DungeonRouteRepository;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('DiscoverService')]
final class DiscoverServiceTest extends PublicTestCase
{
    use CreatesDungeon;

    #[Test]
    public function heroRoutes_givenCurrentSeason_returnsDeduplicatedDungeonRoutes(): void
    {
        // Arrange - the seeded test DB has a current season with dungeons and popular community routes
        $currentSeason = app(SeasonServiceInterface::class)->getCurrentSeason();
        $this->assertNotNull($currentSeason, 'Expected a current season in the seeded test database');

        /** @var DiscoverServiceInterface $discoverService */
        $discoverService = app(DiscoverServiceInterface::class);

        // Act
        $heroRoutes = $discoverService->heroRoutes($currentSeason, 2);

        // Assert - every entry is a DungeonRoute and there are no duplicates by id
        $heroRoutes->each(fn($route) => $this->assertInstanceOf(DungeonRoute::class, $route));
        $this->assertSame(
            $heroRoutes->pluck('id')->unique()->count(),
            $heroRoutes->count(),
            'heroRoutes must be deduplicated by id',
        );
    }

    #[Test]
    public function heroRoutes_givenWeeklyRouteThatIsAlsoTheTopCommunityRoute_returnsItOnce(): void
    {
        // Arrange - without a Raider.IO team to exclude, the weekly route also tops its dungeon's community routes
        $season      = $this->getCurrentSeason();
        $weeklyRoute = null;

        try {
            config(['keystoneguru.raider_io.team_id' => (int)Team::query()->max('id') + 1000]);
            $weeklyRoute = $this->createTopCommunityRoute($season, popularity: 1_000_000);
            $this->bindWeeklyRoutes($weeklyRoute);

            // Act
            $heroRoutes = app(DiscoverServiceInterface::class)->withCache(false)->heroRoutes($season, 1);

            // Assert
            $this->assertSame(1, $heroRoutes->where('id', $weeklyRoute->id)->count(), 'The weekly route must be in the hero routes exactly once');
        } finally {
            $weeklyRoute?->delete();
        }
    }

    #[Test]
    public function heroRoutes_givenRaiderIOTeamRouteWithTheHighestPopularity_leavesItOutOfTheCommunityRoutes(): void
    {
        // Arrange
        $season         = $this->getCurrentSeason();
        $raiderIOTeam   = null;
        $weeklyRoute    = null;
        $communityRoute = null;

        try {
            $raiderIOTeam = Team::create([
                'public_key'  => Team::generateRandomPublicKey(),
                'name'        => 'Hero routes test Raider.IO team',
                'description' => 'Hero routes test Raider.IO team',
                'invite_code' => Team::generateRandomPublicKey(12, 'invite_code'),
            ]);
            config(['keystoneguru.raider_io.team_id' => $raiderIOTeam->id]);
            $weeklyRoute    = $this->createTopCommunityRoute($season, popularity: 1_000_001, attributes: ['team_id' => $raiderIOTeam->id]);
            $communityRoute = $this->createTopCommunityRoute($season, popularity: 1_000_000, dungeonId: $weeklyRoute->dungeon_id);
            $this->bindWeeklyRoutes($weeklyRoute);

            // Act
            $heroRoutes = app(DiscoverServiceInterface::class)->withCache(false)->heroRoutes($season, 1);

            // Assert
            $this->assertSame(1, $heroRoutes->where('id', $weeklyRoute->id)->count(), 'The weekly route must be in the hero routes exactly once');
            $this->assertSame(1, $heroRoutes->where('id', $communityRoute->id)->count(), 'The best community route must not be pushed out by the Raider.IO team route');
        } finally {
            $communityRoute?->delete();
            $weeklyRoute?->delete();
            $raiderIOTeam?->delete();
        }
    }

    #[Test]
    public function popular_givenChildGameVersionMappingTheDungeonItself_returnsTheChildsRouteAndTheParentsRouteOfAnInheritedDungeon(): void
    {
        // Arrange
        $parentRoute    = null;
        $childRoute     = null;
        $inheritedRoute = null;

        try {
            [$parentMappingVersion, $childMappingVersion] = $this->createDungeonMappedByClassicEraAndTbc();
            $parentRoute                                  = $this->createDiscoverableRoute($parentMappingVersion);
            $childRoute                                   = $this->createDiscoverableRoute($childMappingVersion);
            $inheritedRoute                               = $this->createDiscoverableRoute($this->createDungeonMappedByClassicEraOnly());

            // Act
            $routes = $this->discoverServiceForTbc([$parentRoute->id, $childRoute->id, $inheritedRoute->id])->popular();

            // Assert
            $this->assertEqualsCanonicalizing([$childRoute->id, $inheritedRoute->id], $routes->pluck('id')->all());
        } finally {
            $inheritedRoute?->delete();
            $childRoute?->delete();
            $parentRoute?->delete();
        }
    }

    #[Test]
    public function new_givenChildGameVersionMappingTheDungeonItself_returnsTheChildsRouteAndTheParentsRouteOfAnInheritedDungeon(): void
    {
        // Arrange
        $parentRoute    = null;
        $childRoute     = null;
        $inheritedRoute = null;

        try {
            [$parentMappingVersion, $childMappingVersion] = $this->createDungeonMappedByClassicEraAndTbc();
            $parentRoute                                  = $this->createDiscoverableRoute($parentMappingVersion);
            $childRoute                                   = $this->createDiscoverableRoute($childMappingVersion);
            $inheritedRoute                               = $this->createDiscoverableRoute($this->createDungeonMappedByClassicEraOnly());

            // Act
            $routes = $this->discoverServiceForTbc([$parentRoute->id, $childRoute->id, $inheritedRoute->id])->new();

            // Assert
            $this->assertEqualsCanonicalizing([$childRoute->id, $inheritedRoute->id], $routes->pluck('id')->all());
        } finally {
            $inheritedRoute?->delete();
            $childRoute?->delete();
            $parentRoute?->delete();
        }
    }

    private function createDungeonMappedByClassicEraOnly(): MappingVersion
    {
        $dungeon = $this->createDungeon(
            ['expansion_id' => Expansion::ALL[Expansion::EXPANSION_CLASSIC], 'active' => true],
            mappingVersionAttributes: ['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA]],
        );

        return MappingVersion::query()->where('dungeon_id', $dungeon->id)->firstOrFail();
    }

    /**
     * @return array{MappingVersion, MappingVersion} The Classic Era mapping version, then the TBC one
     */
    private function createDungeonMappedByClassicEraAndTbc(): array
    {
        $dungeon = $this->createDungeon(
            ['expansion_id' => Expansion::ALL[Expansion::EXPANSION_CLASSIC], 'active' => true],
            mappingVersionAttributes: ['game_version_id' => GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA]],
        );

        $parentMappingVersion = MappingVersion::query()
            ->where('dungeon_id', $dungeon->id)
            ->where('game_version_id', GameVersion::ALL[GameVersion::GAME_VERSION_CLASSIC_ERA])
            ->firstOrFail();

        $childMappingVersion = MappingVersion::create([
            'game_version_id'                 => GameVersion::ALL[GameVersion::GAME_VERSION_TBC],
            'dungeon_id'                      => $dungeon->id,
            'version'                         => 1,
            'enemy_forces_required'           => 100,
            'enemy_forces_required_teeming'   => null,
            'enemy_forces_shrouded'           => 0,
            'enemy_forces_shrouded_zul_gamux' => 0,
            'timer_max_seconds'               => 1800,
            'facade_enabled'                  => false,
            'mdt_mapping_hash'                => null,
            'mdt_changes_pending'             => false,
        ]);

        return [$parentMappingVersion, $childMappingVersion];
    }

    private function createDiscoverableRoute(MappingVersion $mappingVersion): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'enemy_forces'       => $mappingVersion->enemy_forces_required,
            'expires_at'         => null,
        ]);
    }

    /**
     * @param array<int, int> $routeIds
     */
    private function discoverServiceForTbc(array $routeIds): DiscoverServiceInterface
    {
        return app(DiscoverServiceInterface::class)
            ->withCache(false)
            ->withGameVersion(GameVersion::query()->where('key', GameVersion::GAME_VERSION_TBC)->firstOrFail())
            ->withBuilder(static fn(Builder $builder) => $builder->whereIn('dungeon_routes.id', $routeIds));
    }

    private function getCurrentSeason(): Season
    {
        $season = app(SeasonServiceInterface::class)->getCurrentSeason();
        $this->assertNotNull($season, 'Expected a current season in the seeded test database');

        return $season;
    }

    /**
     * A published route that outranks every seeded route of a dungeon of the season.
     *
     * @param array<string, mixed> $attributes
     */
    private function createTopCommunityRoute(Season $season, int $popularity, ?int $dungeonId = null, array $attributes = []): DungeonRoute
    {
        /** @var Dungeon|null $dungeon */
        $dungeon = $dungeonId === null
            ? $season->dungeons()->active()->get()->first(static fn(Dungeon $dungeon) => $dungeon->getCurrentMappingVersion() !== null)
            : Dungeon::findOrFail($dungeonId);
        $this->assertNotNull($dungeon, 'Expected an active dungeon with a mapping version in the current season');
        $mappingVersion = $dungeon->getCurrentMappingVersion();

        return DungeonRoute::factory()->create(array_merge([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => $season->id,
            'team_id'            => null,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'teeming'            => false,
            'enemy_forces'       => $mappingVersion->enemy_forces_required,
            'popularity'         => $popularity,
            'expires_at'         => null,
        ], $attributes));
    }

    private function bindWeeklyRoutes(DungeonRoute $weeklyRoute): void
    {
        $dungeonRouteRepository = $this->getMockBuilderPublic(DungeonRouteRepository::class)
            ->setConstructorArgs([app()->make(SeasonServiceInterface::class)])
            ->onlyMethods(['getWeeklyRoutes'])
            ->getMock();
        $dungeonRouteRepository->method('getWeeklyRoutes')->willReturn(collect([
            $weeklyRoute->dungeon->key => collect([new WeeklyRoute('pug_friendly', $weeklyRoute)]),
        ]));
        app()->instance(DungeonRouteRepositoryInterface::class, $dungeonRouteRepository);
    }
}
