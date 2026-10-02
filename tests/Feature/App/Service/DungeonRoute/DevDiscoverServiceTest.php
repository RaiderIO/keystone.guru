<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Service\DungeonRoute\DevDiscoverService;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('DiscoverService')]
final class DevDiscoverServiceTest extends PublicTestCase
{
    use CreatesDungeon;

    #[Test]
    public function popular_givenChildGameVersionMappingTheDungeonItself_returnsOnlyTheRouteOnTheChildMappingVersion(): void
    {
        // Arrange
        $parentRoute = null;
        $childRoute  = null;

        try {
            [$parentMappingVersion, $childMappingVersion] = $this->createDungeonMappedByClassicEraAndTbc();
            $parentRoute                                  = $this->createRoute($parentMappingVersion);
            $childRoute                                   = $this->createRoute($childMappingVersion);

            // Act
            $routes = $this->devDiscoverServiceForTbc([$parentRoute->id, $childRoute->id])->popular();

            // Assert
            $this->assertSame([$childRoute->id], $routes->pluck('id')->all());
        } finally {
            $childRoute?->delete();
            $parentRoute?->delete();
        }
    }

    #[Test]
    public function new_givenChildGameVersionMappingTheDungeonItself_returnsOnlyTheRouteOnTheChildMappingVersion(): void
    {
        // Arrange
        $parentRoute = null;
        $childRoute  = null;

        try {
            [$parentMappingVersion, $childMappingVersion] = $this->createDungeonMappedByClassicEraAndTbc();
            $parentRoute                                  = $this->createRoute($parentMappingVersion);
            $childRoute                                   = $this->createRoute($childMappingVersion);

            // Act
            $routes = $this->devDiscoverServiceForTbc([$parentRoute->id, $childRoute->id])->new();

            // Assert
            $this->assertSame([$childRoute->id], $routes->pluck('id')->all());
        } finally {
            $childRoute?->delete();
            $parentRoute?->delete();
        }
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

    private function createRoute(MappingVersion $mappingVersion): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'season_id'          => null,
            'expires_at'         => null,
        ]);
    }

    /**
     * @param array<int, int> $routeIds
     */
    private function devDiscoverServiceForTbc(array $routeIds): DiscoverServiceInterface
    {
        return app(DevDiscoverService::class)
            ->withGameVersion(GameVersion::query()->where('key', GameVersion::GAME_VERSION_TBC)->firstOrFail())
            ->withBuilder(static fn(Builder $builder) => $builder->whereIn('dungeon_routes.id', $routeIds));
    }
}
