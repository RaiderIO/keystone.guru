<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\PublishedState;
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
}
