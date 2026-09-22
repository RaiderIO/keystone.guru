<?php

namespace Tests\Feature\Controller\AdminTools;

use App\Models\CombatLog\ChallengeModeRun;
use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Mapping\MappingVersion;
use App\Models\Season;
use App\Models\User;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Attributes\SlowTest;
use Tests\TestCases\PublicTestCase;

#[Group('Controller')]
#[Group('AdminTools')]
#[SlowTest]
final class AdminToolsAutoRouteCoverageControllerTest extends PublicTestCase
{
    private const int ADMIN_USER_ID     = 1;
    private const int NON_ADMIN_USER_ID = 3;

    /** @var array<int> */
    private array $createdDungeonRouteIds = [];

    /** @var array<int> */
    private array $createdChallengeModeRunIds = [];

    /** @var array<int> */
    private array $createdResolutionIds = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->be(User::findOrFail(self::ADMIN_USER_ID));
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            CombatLogRouteEnemyResolution::query()->whereIn('id', $this->createdResolutionIds)->delete();
            ChallengeModeRun::query()->whereIn('id', $this->createdChallengeModeRunIds)->delete();
            DungeonRoute::query()->whereIn('id', $this->createdDungeonRouteIds)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function index_givenAdminUser_returnsOk(): void
    {
        // Arrange

        // Act
        $response = $this->get(route('admin.tools.combatlog.route.coverage.view'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('admin.tools.combatlog.route.coverage');
        $response->assertViewHas('dungeons');
        $response->assertViewHas('days', 7);
    }

    #[Test]
    public function index_givenNonAdminUser_returnsForbidden(): void
    {
        // Arrange
        $this->be(User::findOrFail(self::NON_ADMIN_USER_ID));

        // Act
        $response = $this->get(route('admin.tools.combatlog.route.coverage.view'));

        // Assert
        $response->assertForbidden();
    }

    #[Test]
    public function index_givenRoutesInEachBucket_returnsCorrectBucketCounts(): void
    {
        // Arrange
        $dungeon  = $this->getCurrentSeasonDungeon();
        $required = $dungeon->getCurrentMappingVersion()->enemy_forces_required;
        $before   = $this->getBucketsForDungeon($dungeon);

        $this->createAutoRoute($dungeon, (int)round($required * 0.90));
        $this->createAutoRoute($dungeon, (int)round($required * 0.97));
        $this->createAutoRoute($dungeon, $required);
        $this->createAutoRoute($dungeon, $required + 1);

        // Act
        $added = $this->getAddedBuckets($before, $this->getBucketsForDungeon($dungeon));

        // Assert
        $this->assertSame(
            ['critical' => 1, 'warning' => 1, 'ok' => 1, 'over' => 1, 'unknown' => 0],
            $added,
        );
    }

    #[Test]
    public function index_givenRouteWithoutChallengeModeRun_excludesRoute(): void
    {
        // Arrange
        $dungeon = $this->getCurrentSeasonDungeon();
        $before  = $this->getBucketsForDungeon($dungeon);

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
            'enemy_forces'       => 0,
        ]);
        $this->createdDungeonRouteIds[] = $dungeonRoute->id;

        // Act
        $added = $this->getAddedBuckets($before, $this->getBucketsForDungeon($dungeon));

        // Assert
        $this->assertSame(0, array_sum($added));
    }

    #[Test]
    public function index_givenFailedChallengeModeRun_excludesRoute(): void
    {
        // Arrange - a depleted run legitimately never reached 100% and must not show up as an ARC problem
        $dungeon = $this->getCurrentSeasonDungeon();
        $before  = $this->getBucketsForDungeon($dungeon);

        $this->createAutoRoute($dungeon, 0, failed: true);

        // Act
        $added = $this->getAddedBuckets($before, $this->getBucketsForDungeon($dungeon));

        // Assert
        $this->assertSame(0, array_sum($added));
    }

    #[Test]
    public function index_givenRunOutsideDaysWindow_excludesRoute(): void
    {
        // Arrange
        $dungeon             = $this->getCurrentSeasonDungeon();
        $beforeDefaultWindow = $this->getBucketsForDungeon($dungeon);
        $beforeWiderWindow   = $this->getBucketsForDungeon($dungeon, 60);

        $this->createAutoRoute($dungeon, 0, createdAt: Carbon::now()->subDays(30));

        // Act
        $addedDefaultWindow = $this->getAddedBuckets($beforeDefaultWindow, $this->getBucketsForDungeon($dungeon));
        $addedWiderWindow   = $this->getAddedBuckets($beforeWiderWindow, $this->getBucketsForDungeon($dungeon, 60));

        // Assert
        $this->assertSame(0, array_sum($addedDefaultWindow));
        $this->assertSame(1, array_sum($addedWiderWindow));
    }

    #[Test]
    public function index_givenMappingVersionWithoutRequiredForces_countsAsUnknown(): void
    {
        // Arrange
        $dungeon        = $this->getCurrentSeasonDungeon();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $before         = $this->getBucketsForDungeon($dungeon);

        $originalRequired = $mappingVersion->enemy_forces_required;
        MappingVersion::query()->whereKey($mappingVersion->id)->update(['enemy_forces_required' => 0]);

        try {
            $this->createAutoRoute($dungeon, 100);

            // Act
            $added = $this->getAddedBuckets($before, $this->getBucketsForDungeon($dungeon));

            // Assert - a mapping data gap must not be reported as an Auto Route Creator failure
            $this->assertSame(1, $added['unknown']);
            $this->assertSame(0, $added['critical']);
        } finally {
            MappingVersion::query()->whereKey($mappingVersion->id)->update(['enemy_forces_required' => $originalRequired]);
        }
    }

    /**
     * An imported row's dungeon_route_id names a route of the deployment it came from, so it must not be counted
     * against the local route that happens to share the id.
     */
    #[Test]
    public function index_givenRouteWithResolutions_countsOnlyLocallyRecordedOnes(): void
    {
        // Arrange - 0 enemy forces sorts the route first, inside the listed worst routes
        $dungeon      = $this->getCurrentSeasonDungeon();
        $dungeonRoute = $this->createAutoRoute($dungeon, 0);
        $this->createResolutions($dungeonRoute, 3);
        $this->createResolutions($dungeonRoute, 2, 'production');

        // Act
        $route = $this->getRouteRow($dungeon, $dungeonRoute);

        // Assert
        $this->assertSame(3, $route['enemyResolutionCount']);
    }

    #[Test]
    public function index_givenRouteWithoutResolutions_countsZero(): void
    {
        // Arrange
        $dungeon      = $this->getCurrentSeasonDungeon();
        $dungeonRoute = $this->createAutoRoute($dungeon, 0);

        // Act
        $route = $this->getRouteRow($dungeon, $dungeonRoute);

        // Assert
        $this->assertSame(0, $route['enemyResolutionCount']);
    }

    /**
     * Resolutions are pruned after the retention window, so an older route having none says nothing about it.
     */
    #[Test]
    public function index_givenRunOlderThanResolutionRetention_countsNothing(): void
    {
        // Arrange
        $dungeon      = $this->getCurrentSeasonDungeon();
        $retention    = (int)config('keystoneguru.enemy_resolution.retention_days');
        $dungeonRoute = $this->createAutoRoute($dungeon, 0, createdAt: Carbon::now()->subDays($retention + 5));

        // Act
        $route = $this->getRouteRow($dungeon, $dungeonRoute, $retention + 30);

        // Assert
        $this->assertNull($route['enemyResolutionCount']);
    }

    /**
     * Regenerating an old run's route records fresh resolutions, so those count whenever the run itself happened.
     */
    #[Test]
    public function index_givenOldRunWithFreshResolutions_countsThem(): void
    {
        // Arrange
        $dungeon      = $this->getCurrentSeasonDungeon();
        $retention    = (int)config('keystoneguru.enemy_resolution.retention_days');
        $dungeonRoute = $this->createAutoRoute($dungeon, 0, createdAt: Carbon::now()->subDays($retention + 5));
        $this->createResolutions($dungeonRoute, 2);

        // Act
        $route = $this->getRouteRow($dungeon, $dungeonRoute, $retention + 30);

        // Assert
        $this->assertSame(2, $route['enemyResolutionCount']);
    }

    #[Test]
    public function index_givenRoutesWithResolutions_averagesThemPerDungeon(): void
    {
        // Arrange
        $dungeon = $this->getCurrentSeasonDungeon();
        $first   = $this->createAutoRoute($dungeon, 0);
        $second  = $this->createAutoRoute($dungeon, 0);
        $this->createResolutions($first, 3);
        $this->createResolutions($second, 1);

        // Act
        $row = $this->getDungeonRow($dungeon);

        // Assert - the seeded database may hold more routes; the average must match the routes it describes
        if ($row['hiddenRouteCount'] > 0) {
            $this->markTestSkipped('Too many seeded routes to check the average against the listed ones');
        }
        $countsByRouteId = $row['routes']->mapWithKeys(static fn(array $route) => [$route['dungeonRoute']->id => $route['enemyResolutionCount']]);
        $this->assertSame(3, $countsByRouteId->get($first->id));
        $this->assertSame(1, $countsByRouteId->get($second->id));
        $counts = $row['routes']->pluck('enemyResolutionCount')->filter(static fn(?int $count) => $count !== null);
        $this->assertSame(round($counts->sum() / $counts->count(), 1), $row['resolutionsPerRoute']);
    }

    /**
     * The bucket counts the page reports for a single dungeon. The seeded test database may already hold Auto Route
     * Creator routes, so tests compare a before/after snapshot rather than absolute numbers.
     *
     * @return array<string, int>
     */
    private function getBucketsForDungeon(Dungeon $dungeon, ?int $days = null): array
    {
        return $this->getDungeonRow($dungeon, $days)['buckets'];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDungeonRow(Dungeon $dungeon, ?int $days = null): array
    {
        $response = $this->get(route('admin.tools.combatlog.route.coverage.view', $days === null ? [] : ['days' => $days]));
        $response->assertOk();

        /** @var Collection<int, array<string, mixed>> $dungeons */
        $dungeons = $response->viewData('dungeons');

        /** @var array<string, mixed>|null $row */
        $row = $dungeons->first(static fn(array $row): bool => $row['dungeon']->id === $dungeon->id);

        $this->assertNotNull($row, sprintf('Dungeon %d is not part of the current season overview', $dungeon->id));

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function getRouteRow(Dungeon $dungeon, DungeonRoute $dungeonRoute, ?int $days = null): array
    {
        /** @var array<string, mixed>|null $route */
        $route = $this->getDungeonRow($dungeon, $days)['routes']
            ->first(static fn(array $route): bool => $route['dungeonRoute']->id === $dungeonRoute->id);

        $this->assertNotNull($route, sprintf('Route %d is not among the listed worst routes', $dungeonRoute->id));

        return $route;
    }

    private function createResolutions(DungeonRoute $dungeonRoute, int $count, ?string $source = null): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->createdResolutionIds[] = CombatLogRouteEnemyResolution::factory()->create([
                'dungeon_route_id' => $dungeonRoute->id,
                'source'           => $source,
            ])->id;
        }
    }

    /**
     * @param array<string, int> $before
     * @param array<string, int> $after
     *
     * @return array<string, int>
     */
    private function getAddedBuckets(array $before, array $after): array
    {
        $added = [];
        foreach ($after as $bucket => $count) {
            $added[$bucket] = $count - ($before[$bucket] ?? 0);
        }

        return $added;
    }

    private function createAutoRoute(
        Dungeon $dungeon,
        int     $enemyForces,
        ?Carbon $createdAt = null,
        bool    $failed = false,
    ): DungeonRoute {
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
            'enemy_forces'       => $enemyForces,
        ]);
        $this->createdDungeonRouteIds[] = $dungeonRoute->id;

        $factory = ChallengeModeRun::factory()->createdAt($createdAt ?? Carbon::now());
        if ($failed) {
            $factory = $factory->failed();
        }

        $challengeModeRun = $factory->create([
            'dungeon_id'       => $dungeon->id,
            'dungeon_route_id' => $dungeonRoute->id,
        ]);
        $this->createdChallengeModeRunIds[] = $challengeModeRun->id;

        return $dungeonRoute;
    }

    private function getCurrentSeasonDungeon(): Dungeon
    {
        /** @var SeasonServiceInterface $seasonService */
        $seasonService = app()->make(SeasonServiceInterface::class);

        /** @var Season|null $season */
        $season = $seasonService->getCurrentSeason();

        $this->assertNotNull($season, 'There is no current season in the test database');

        /** @var Dungeon|null $dungeon */
        $dungeon = $season->dungeons()->get()->first(
            static fn(Dungeon $dungeon): bool => $dungeon->getCurrentMappingVersion()->enemy_forces_required > 0,
        );

        $this->assertNotNull($dungeon, 'No current season dungeon has an enemy forces requirement');

        return $dungeon;
    }
}
