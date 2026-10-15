<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\GameVersion\GameVersion;
use App\Models\Mapping\MappingVersion;
use App\Models\PageView;
use App\Models\PublishedState;
use App\Service\DungeonRoute\DungeonRouteServiceInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Traits\CreatesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRouteService')]
final class DungeonRouteServiceUpdatePopularityTest extends PublicTestCase
{
    use CreatesDungeon;

    private const int VIEW_COUNT = 4;

    #[Test]
    public function updatePopularity_givenRoutesOnTheLatestAndAnOutdatedMappingVersion_penalizesOnlyTheOutdatedRoute(): void
    {
        // Arrange
        $currentRoute  = null;
        $outdatedRoute = null;

        try {
            $dungeon = $this->createDungeon();
            /** @var MappingVersion $outdatedMappingVersion */
            $outdatedMappingVersion = $dungeon->mappingVersions()->firstOrFail();
            $latestMappingVersion   = MappingVersion::create([
                'game_version_id'                 => GameVersion::getDefaultGameVersion()->id,
                'dungeon_id'                      => $dungeon->id,
                'version'                         => 2,
                'enemy_forces_required'           => 100,
                'enemy_forces_required_teeming'   => null,
                'enemy_forces_shrouded'           => 0,
                'enemy_forces_shrouded_zul_gamux' => 0,
                'timer_max_seconds'               => 1800,
                'facade_enabled'                  => false,
                'mdt_mapping_hash'                => null,
                'mdt_changes_pending'             => false,
            ]);
            $currentRoute  = $this->createViewedRoute($dungeon->id, $latestMappingVersion->id);
            $outdatedRoute = $this->createViewedRoute($dungeon->id, $outdatedMappingVersion->id);

            // Act
            app(DungeonRouteServiceInterface::class)->updatePopularity();

            // Assert
            $penalty = config('keystoneguru.discover.service.popular_out_of_date_mapping_version_penalty');
            $this->assertSame(self::VIEW_COUNT, $currentRoute->refresh()->popularity);
            $this->assertSame((int)(self::VIEW_COUNT * $penalty), $outdatedRoute->refresh()->popularity);
        } finally {
            $this->deleteRoutesAndTheirPageViews([$currentRoute, $outdatedRoute]);
        }
    }

    #[Test]
    public function updatePopularity_givenViewedRoute_neverReadsPageViewsInsideAWriteStatement(): void
    {
        // Arrange
        $route = null;

        try {
            $dungeon = $this->createDungeon();
            $route   = $this->createViewedRoute($dungeon->id, $dungeon->mappingVersions()->firstOrFail()->id);

            /** @var string[] $writeStatements */
            $writeStatements = [];
            DB::listen(static function (QueryExecuted $query) use (&$writeStatements): void {
                if (preg_match('/^\s*(UPDATE|INSERT|DELETE|REPLACE)\b/i', $query->sql)) {
                    $writeStatements[] = $query->sql;
                }
            });

            // Act
            app(DungeonRouteServiceInterface::class)->updatePopularity();

            // Assert
            $this->assertSame(self::VIEW_COUNT, $route->refresh()->popularity);
            $this->assertNotEmpty($writeStatements);
            foreach ($writeStatements as $writeStatement) {
                $this->assertDoesNotMatchRegularExpression('/\bpage_views\b/', $writeStatement);
            }
        } finally {
            $this->deleteRoutesAndTheirPageViews([$route]);
        }
    }

    #[Test]
    public function updatePopularity_givenPageViewsOlderThanThePopularWindow_countsOnlyTheRecentViews(): void
    {
        // Arrange
        $route = null;

        try {
            $dungeon = $this->createDungeon();
            $route   = $this->createViewedRoute($dungeon->id, $dungeon->mappingVersions()->firstOrFail()->id);
            $this->createPageViews(
                $route,
                2,
                now()->subDays(config('keystoneguru.discover.service.popular_days') + 1),
            );

            // Act
            app(DungeonRouteServiceInterface::class)->updatePopularity();

            // Assert
            $this->assertSame(self::VIEW_COUNT, $route->refresh()->popularity);
        } finally {
            $this->deleteRoutesAndTheirPageViews([$route]);
        }
    }

    #[Test]
    public function updatePopularity_givenRouteWithoutRecentPageViews_keepsItsPopularity(): void
    {
        // Arrange
        $viewedRoute   = null;
        $unviewedRoute = null;

        try {
            $dungeon       = $this->createDungeon();
            $viewedRoute   = $this->createViewedRoute($dungeon->id, $dungeon->mappingVersions()->firstOrFail()->id);
            $unviewedRoute = $this->createRoute($dungeon->id, $dungeon->mappingVersions()->firstOrFail()->id, 7);

            // Act
            app(DungeonRouteServiceInterface::class)->updatePopularity();

            // Assert
            $this->assertSame(self::VIEW_COUNT, $viewedRoute->refresh()->popularity);
            $this->assertSame(7, $unviewedRoute->refresh()->popularity);
        } finally {
            $this->deleteRoutesAndTheirPageViews([$viewedRoute, $unviewedRoute]);
        }
    }

    private function createRoute(int $dungeonId, int $mappingVersionId, int $popularity = 0): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeonId,
            'mapping_version_id' => $mappingVersionId,
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
            'expires_at'         => null,
            'clone_of'           => null,
            'popularity'         => $popularity,
        ]);
    }

    private function createPageViews(DungeonRoute $route, int $count, ?Carbon $createdAt = null): void
    {
        for ($i = 0; $i < $count; $i++) {
            $pageView = PageView::create([
                'user_id'     => -1,
                'model_id'    => $route->id,
                'model_class' => DungeonRoute::class,
                'session_id'  => sprintf('update-popularity-test-%d', $i),
            ]);

            if ($createdAt !== null) {
                PageView::query()->whereKey($pageView->id)->update(['created_at' => $createdAt]);
            }
        }
    }

    /**
     * @param array<int, DungeonRoute|null> $routes
     */
    private function deleteRoutesAndTheirPageViews(array $routes): void
    {
        foreach ($routes as $route) {
            if ($route !== null) {
                PageView::query()
                    ->where('model_class', DungeonRoute::class)
                    ->where('model_id', $route->id)
                    ->delete();
                $route->delete();
            }
        }
    }

    private function createViewedRoute(int $dungeonId, int $mappingVersionId): DungeonRoute
    {
        $route = $this->createRoute($dungeonId, $mappingVersionId);
        $this->createPageViews($route, self::VIEW_COUNT);

        return $route;
    }
}
