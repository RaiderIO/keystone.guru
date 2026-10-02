<?php

namespace Tests\Feature\App\Service\Dungeon\DungeonService;

use App\Models\Dungeon;
use App\Repositories\Interfaces\DungeonRepositoryInterface;
use App\Repositories\Interfaces\PageViewCountRepositoryInterface;
use App\Service\Cache\CacheServiceInterface;
use App\Service\Cookies\CookieServiceInterface;
use App\Service\Dungeon\DungeonService;
use App\Service\Dungeon\Logging\DungeonServiceLoggingInterface;
use App\Service\GameVersion\GameVersionServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonService')]
#[Group('GetViewShares')]
final class GetViewSharesTest extends PublicTestCase
{
    #[Test]
    public function getViewShares_givenViewedDungeons_returnsEachDungeonsViewsAsAShareOfTheMostViewed(): void
    {
        // Arrange
        $dungeons = $this->makeDungeons([1, 2, 3]);
        $service  = $this->buildService(collect([1 => 50, 2 => 200, 3 => 100]));

        // Act
        $result = $service->getViewShares($dungeons);

        // Assert
        $this->assertSame([1 => 0.25, 2 => 1.0, 3 => 0.5], $result->all());
    }

    #[Test]
    public function getViewShares_givenADungeonWithoutViews_returnsZeroForIt(): void
    {
        // Arrange
        $dungeons = $this->makeDungeons([1, 2]);
        $service  = $this->buildService(collect([1 => 40]));

        // Act
        $result = $service->getViewShares($dungeons);

        // Assert
        $this->assertSame([1 => 1.0, 2 => 0.0], $result->all());
    }

    #[Test]
    public function getViewShares_givenViewsOfADungeonOutsideTheList_comparesOnlyTheListedDungeons(): void
    {
        // Arrange
        $dungeons = $this->makeDungeons([1, 2]);
        $service  = $this->buildService(collect([1 => 10, 2 => 5, 99 => 1000]));

        // Act
        $result = $service->getViewShares($dungeons);

        // Assert
        $this->assertSame([1 => 1.0, 2 => 0.5], $result->all());
    }

    #[Test]
    public function getViewShares_givenNoListedDungeonWasViewed_returnsEmpty(): void
    {
        // Arrange
        $dungeons = $this->makeDungeons([1, 2]);
        $service  = $this->buildService(collect([99 => 1000]));

        // Act
        $result = $service->getViewShares($dungeons);

        // Assert
        $this->assertTrue($result->isEmpty());
    }

    #[Test]
    public function getViewShares_givenTheConfiguredWindow_readsTheViewsSinceItsFirstDay(): void
    {
        // Arrange
        config(['keystoneguru.page_views.dungeon_views_days' => 14]);
        $since                   = null;
        $pageViewCountRepository = $this->createMockPublic(PageViewCountRepositoryInterface::class);
        $pageViewCountRepository->method('getViewsPerDungeon')->willReturnCallback(static function (Carbon $from) use (&$since): Collection {
            $since = $from;

            return collect();
        });
        $service = $this->buildServiceWith($pageViewCountRepository);

        // Act
        $service->getViewShares($this->makeDungeons([1]));

        // Assert
        $this->assertSame(Carbon::today()->subDays(14)->toDateString(), $since?->toDateString());
    }

    /**
     * @param  array<int, int>          $ids
     * @return Collection<int, Dungeon>
     */
    private function makeDungeons(array $ids): Collection
    {
        return collect($ids)->map(static function (int $id): Dungeon {
            $dungeon     = new Dungeon();
            $dungeon->id = $id;

            return $dungeon;
        });
    }

    /**
     * @param Collection<int, int> $viewsPerDungeon
     */
    private function buildService(Collection $viewsPerDungeon): DungeonService
    {
        $pageViewCountRepository = $this->createMockPublic(PageViewCountRepositoryInterface::class);
        $pageViewCountRepository->method('getViewsPerDungeon')->willReturn($viewsPerDungeon);

        return $this->buildServiceWith($pageViewCountRepository);
    }

    private function buildServiceWith(PageViewCountRepositoryInterface $pageViewCountRepository): DungeonService
    {
        $cacheService = $this->createMockPublic(CacheServiceInterface::class);
        $cacheService->method('remember')->willReturnCallback(static fn(string $key, mixed $value) => $value());

        return new DungeonService(
            $this->createMockPublic(CookieServiceInterface::class),
            $this->createMockPublic(SeasonServiceInterface::class),
            $this->createMockPublic(DungeonServiceLoggingInterface::class),
            $this->createMockPublic(GameVersionServiceInterface::class),
            $this->createMockPublic(DungeonRepositoryInterface::class),
            $pageViewCountRepository,
            $cacheService,
        );
    }
}
