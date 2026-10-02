<?php

namespace Tests\Feature\Console\Commands\Scheduler\PageView;

use App\Console\Commands\Scheduler\PageView\Prune;
use App\Models\PageView;
use App\Models\PageViewCount;
use App\Repositories\Interfaces\PageViewCountRepositoryInterface;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('PageView')]
final class PruneTest extends PublicTestCase
{
    /** @var array<int> */
    private array $createdIds = [];

    private int $maxPageViewCountIdBefore = 0;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->maxPageViewCountIdBefore = (int)PageViewCount::query()->max('id');
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            PageView::query()->whereIn('id', $this->createdIds)->delete();
            PageViewCount::query()->where('id', '>', $this->maxPageViewCountIdBefore)->delete();
        } finally {
            parent::tearDown();
        }
    }

    #[Test]
    public function handle_givenOldPageViews_deletesThemAndKeepsRecent(): void
    {
        // Arrange
        $retentionDays = config('keystoneguru.page_views.retention_days');

        $old = PageView::forceCreate([
            'user_id'     => -1,
            'model_id'    => 999999,
            'model_class' => 'TestModel',
            'session_id'  => 'test-session-old',
            'source'      => null,
            'created_at'  => Carbon::now()->subDays($retentionDays + 1),
            'updated_at'  => Carbon::now()->subDays($retentionDays + 1),
        ]);
        $this->createdIds[] = $old->id;

        $recent = PageView::forceCreate([
            'user_id'     => -1,
            'model_id'    => 999999,
            'model_class' => 'TestModel',
            'session_id'  => 'test-session-recent',
            'source'      => null,
            'created_at'  => Carbon::now()->subDays($retentionDays - 1),
            'updated_at'  => Carbon::now()->subDays($retentionDays - 1),
        ]);
        $this->createdIds[] = $recent->id;

        // Act
        $this->artisan(Prune::class)->assertSuccessful();

        // Assert
        $this->assertDatabaseMissing('page_views', ['id' => $old->id]);
        $this->assertDatabaseHas('page_views', ['id' => $recent->id]);

        // Remove recent from cleanup since the command already removed old
        $this->createdIds = array_filter($this->createdIds, static fn(int $id) => $id === $recent->id);
    }

    #[Test]
    public function handle_givenPageViewsOfYesterday_aggregatesThemPerModelAndSourceBeforePruning(): void
    {
        // Arrange
        $retentionDays = config('keystoneguru.page_views.retention_days');
        $yesterday     = Carbon::yesterday()->setTime(12, 0);
        $oldDay        = Carbon::today()->subDays($retentionDays + 1)->setTime(12, 0);

        $this->createPageView(999999, 1, $yesterday);
        $this->createPageView(999999, 1, $yesterday);
        $this->createPageView(999999, 1, $yesterday);
        $this->createPageView(999999, null, $yesterday);
        $this->createPageView(999998, 1, $yesterday);
        $this->createPageView(999999, 1, Carbon::now());
        $old = $this->createPageView(999999, 1, $oldDay);

        // Act
        $this->artisan(Prune::class)->assertSuccessful();

        // Assert
        $this->assertSame(3, $this->getViews(999999, 1, $yesterday));
        $this->assertSame(1, $this->getViews(999999, 0, $yesterday));
        $this->assertSame(1, $this->getViews(999998, 1, $yesterday));
        $this->assertNull($this->getViews(999999, 1, Carbon::today()), 'Today is still collecting page views');
        $this->assertNull($this->getViews(999999, 1, $oldDay), 'Days outside the retention window are not aggregated');
        $this->assertDatabaseMissing('page_views', ['id' => $old->id]);
    }

    #[Test]
    public function handle_givenAnAggregatedDay_onlyAggregatesTheDaysAfterIt(): void
    {
        // Arrange
        $latestAggregatedDay = Carbon::today()->subDays(3);
        PageViewCount::factory()->create([
            'model_class' => 'TestModel',
            'model_id'    => 999999,
            'source'      => 1,
            'viewed_on'   => $latestAggregatedDay->toDateString(),
            'views'       => 1,
        ]);

        $before = $this->createPageView(999999, 1, Carbon::today()->subDays(4)->setTime(12, 0));
        $after  = $this->createPageView(999999, 1, Carbon::today()->subDays(2)->setTime(12, 0));

        // Act
        $this->artisan(Prune::class)->assertSuccessful();

        // Assert
        $this->assertNull($this->getViews(999999, 1, Carbon::parse($before->created_at)));
        $this->assertSame(1, $this->getViews(999999, 1, Carbon::parse($after->created_at)));
    }

    #[Test]
    public function handle_givenAggregationFails_doesNotPrune(): void
    {
        // Arrange
        $retentionDays = config('keystoneguru.page_views.retention_days');
        $old           = $this->createPageView(999999, 1, Carbon::now()->subDays($retentionDays + 1));

        $pageViewCountRepository = $this->createMockPublic(PageViewCountRepositoryInterface::class);
        $pageViewCountRepository->method('getLatestViewedOn')->willReturn(null);
        $pageViewCountRepository->method('aggregateDay')->willThrowException(new RuntimeException('Aggregation failed'));
        app()->instance(PageViewCountRepositoryInterface::class, $pageViewCountRepository);

        // Act
        $this->artisan(Prune::class)->assertFailed();

        // Assert
        $this->assertDatabaseHas('page_views', ['id' => $old->id]);
    }

    private function createPageView(int $modelId, ?int $source, Carbon $createdAt): PageView
    {
        $pageView = PageView::forceCreate([
            'user_id'     => -1,
            'model_id'    => $modelId,
            'model_class' => 'TestModel',
            'session_id'  => 'test-session',
            'source'      => $source,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
        ]);

        $this->createdIds[] = $pageView->id;

        return $pageView;
    }

    private function getViews(int $modelId, int $source, Carbon $day): ?int
    {
        return PageViewCount::query()
            ->where('model_class', 'TestModel')
            ->where('model_id', $modelId)
            ->where('source', $source)
            ->whereDate('viewed_on', $day->toDateString())
            ->value('views');
    }
}
