<?php

namespace Tests\Feature\App\Repository;

use App\Models\PageView;
use App\Models\PageViewCount;
use App\Repositories\Database\PageViewCountRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
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
