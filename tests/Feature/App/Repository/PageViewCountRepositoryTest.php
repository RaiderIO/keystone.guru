<?php

namespace Tests\Feature\App\Repository;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PageView;
use App\Models\PageViewCount;
use App\Repositories\Database\PageViewCountRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('PageView')]
final class PageViewCountRepositoryTest extends PublicTestCase
{
    private PageViewCountRepository $repository;

    /** @var array<int> */
    private array $createdIds = [];

    private int $maxPageViewCountIdBefore = 0;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository               = new PageViewCountRepository();
        $this->maxPageViewCountIdBefore = (int)PageViewCount::query()->max('id');
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            PageView::query()->whereIn('id', $this->createdIds)->delete();
            PageView::query()->where('model_class', 'TestModel')->delete();
            PageViewCount::query()->where('id', '>', $this->maxPageViewCountIdBefore)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function aggregateDay_givenDayAtItsBoundaries_countsOnlyThatDay(): void
    {
        // Arrange
        $day = Carbon::today()->subDays(2);
        $this->createPageView($day->copy()->subSecond());
        $this->createPageView($day->copy());
        $this->createPageView($day->copy()->setTime(23, 59, 59));
        $this->createPageView($day->copy()->addDay());

        // Act
        $this->repository->aggregateDay($day);

        // Assert
        $this->assertSame(2, $this->getViews($day));
    }

    #[Test]
    public function aggregateDay_givenAlreadyAggregatedDay_replacesItsCount(): void
    {
        // Arrange
        $day = Carbon::today()->subDays(2);
        $this->createPageView($day->copy()->setTime(10, 0));
        $this->repository->aggregateDay($day);
        $this->createPageView($day->copy()->setTime(11, 0));

        // Act
        $this->repository->aggregateDay($day);

        // Assert
        $this->assertSame(2, $this->getViews($day));
        $this->assertSame(1, PageViewCount::query()->where('model_class', 'TestModel')->count());
    }

    #[Test]
    public function aggregateDay_givenLaterChunkFails_writesNothingForThatDay(): void
    {
        // Arrange
        $day       = Carbon::today()->subDays(2);
        $createdAt = $day->copy()->setTime(12, 0);
        $rows      = [];
        for ($modelId = 1; $modelId <= 1001; $modelId++) {
            $rows[] = $this->getPageViewAttributes($modelId, $createdAt);
        }
        PageView::query()->insert($rows);

        $countInserts = 0;
        DB::connection()->beforeExecuting(static function (string $query) use (&$countInserts): void {
            if (str_starts_with($query, 'insert into `page_view_counts`') && ++$countInserts === 2) {
                throw new RuntimeException('Second chunk failed');
            }
        });

        // Act
        $exception = null;

        try {
            $this->repository->aggregateDay($day);
        } catch (RuntimeException $runtimeException) {
            $exception = $runtimeException;
        }

        // Assert
        $this->assertSame('Second chunk failed', $exception?->getMessage());
        $this->assertSame(0, PageViewCount::query()->where('model_class', 'TestModel')->count());
    }

    #[Test]
    public function getLatestViewedOn_givenCounts_returnsTheMostRecentDay(): void
    {
        // Arrange
        $latestDay = Carbon::today()->subDays(2);
        $this->createPageView(Carbon::today()->subDays(5)->setTime(12, 0));
        $this->createPageView($latestDay->copy()->setTime(12, 0));
        $this->repository->aggregateDay(Carbon::today()->subDays(5));
        $this->repository->aggregateDay($latestDay);

        // Act
        $result = $this->repository->getLatestViewedOn();

        // Assert
        $this->assertSame($latestDay->toDateString(), $result?->toDateString());
    }

    #[Test]
    public function getViewsPerDungeon_givenDungeonAndRouteViews_sumsThemPerDungeon(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create();

        try {
            $dungeonId = $dungeonRoute->dungeon_id;
            $this->createDungeonViewCount($dungeonId, Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON, 5);
            $this->createRouteViewCount($dungeonRoute->id, DungeonRoute::PAGE_VIEW_SOURCE_VIEW_ROUTE, 3);
            $this->createRouteViewCount($dungeonRoute->id, DungeonRoute::PAGE_VIEW_SOURCE_PRESENT_ROUTE, 2);

            // Act
            $result = $this->repository->getViewsPerDungeon(Carbon::today()->subDays(7));

            // Assert
            $this->assertSame(10, $result->get($dungeonId));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getViewsPerDungeon_givenEmbedViews_leavesThemOut(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create();

        try {
            $dungeonId = $dungeonRoute->dungeon_id;
            $this->createDungeonViewCount($dungeonId, Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON, 1);
            $this->createDungeonViewCount($dungeonId, Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON_EMBED, 20);
            $this->createDungeonViewCount($dungeonId, Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON_HEATMAP_EMBED, 40);
            $this->createRouteViewCount($dungeonRoute->id, DungeonRoute::PAGE_VIEW_SOURCE_VIEW_EMBED, 80);

            // Act
            $result = $this->repository->getViewsPerDungeon(Carbon::today()->subDays(7));

            // Assert
            $this->assertSame(1, $result->get($dungeonId));
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getViewsPerDungeon_givenViewsBeforeSince_leavesThemOut(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create();
        $since        = Carbon::today()->subDays(7);

        try {
            $dungeonId = $dungeonRoute->dungeon_id;
            $this->createDungeonViewCount($dungeonId, Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON, 1, $since);
            $this->createDungeonViewCount($dungeonId, Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON, 20, $since->copy()->subDay());
            $this->createRouteViewCount($dungeonRoute->id, DungeonRoute::PAGE_VIEW_SOURCE_VIEW_ROUTE, 2, $since);
            $this->createRouteViewCount($dungeonRoute->id, DungeonRoute::PAGE_VIEW_SOURCE_VIEW_ROUTE, 40, $since->copy()->subDay());

            // Act
            $result = $this->repository->getViewsPerDungeon($since);

            // Assert
            $this->assertSame(3, $result->get($dungeonId));
        } finally {
            $dungeonRoute->delete();
        }
    }

    private function createDungeonViewCount(int $dungeonId, int $source, int $views, ?Carbon $viewedOn = null): void
    {
        PageViewCount::factory()->create([
            'model_class' => Dungeon::class,
            'model_id'    => $dungeonId,
            'source'      => $source,
            'viewed_on'   => ($viewedOn ?? Carbon::yesterday())->toDateString(),
            'views'       => $views,
        ]);
    }

    private function createRouteViewCount(int $dungeonRouteId, int $source, int $views, ?Carbon $viewedOn = null): void
    {
        PageViewCount::factory()->create([
            'model_class' => DungeonRoute::class,
            'model_id'    => $dungeonRouteId,
            'source'      => $source,
            'viewed_on'   => ($viewedOn ?? Carbon::yesterday())->toDateString(),
            'views'       => $views,
        ]);
    }

    private function createPageView(Carbon $createdAt): void
    {
        $pageView = PageView::forceCreate([
            'user_id'     => -1,
            'model_id'    => 999999,
            'model_class' => 'TestModel',
            'session_id'  => 'test-session',
            'source'      => 1,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
        ]);

        $this->createdIds[] = $pageView->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function getPageViewAttributes(int $modelId, Carbon $createdAt): array
    {
        return [
            'user_id'     => -1,
            'model_id'    => $modelId,
            'model_class' => 'TestModel',
            'session_id'  => 'test-session',
            'source'      => 1,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
        ];
    }

    private function getViews(Carbon $day): ?int
    {
        return PageViewCount::query()
            ->where('model_class', 'TestModel')
            ->where('model_id', 999999)
            ->where('source', 1)
            ->whereDate('viewed_on', $day->toDateString())
            ->value('views');
    }
}
