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
#[Group('GetPopularDungeonIds')]
final class GetPopularDungeonIdsTest extends PublicTestCase
{
    #[Test]
    public function getPopularDungeonIds_givenEightViewedDungeons_returnsTheTwoMostViewedMostViewedFirst(): void
    {
        // Arrange
        $dungeons = $this->makeDungeons(range(1, 8));
        $service  = $this->buildService(collect([1 => 10, 2 => 50, 3 => 5, 4 => 70, 5 => 20, 6 => 1, 7 => 30, 8 => 2]));

        // Act
        $result = $service->getPopularDungeonIds($dungeons);

        // Assert
        $this->assertSame([4, 2], $result->all());
    }

    #[Test]
    public function getPopularDungeonIds_givenNineDungeons_roundsTheQuarterUp(): void
    {
        // Arrange
        $dungeons = $this->makeDungeons(range(1, 9));
        $service  = $this->buildService(collect([1 => 10, 2 => 20, 3 => 30, 4 => 40, 5 => 50, 6 => 60, 7 => 70, 8 => 80, 9 => 90]));

        // Act
        $result = $service->getPopularDungeonIds($dungeons);

        // Assert
        $this->assertSame([9, 8, 7], $result->all());
    }

    #[Test]
    public function getPopularDungeonIds_givenOnlyOneViewedDungeon_returnsJustThatOne(): void
    {
        // Arrange
        $dungeons = $this->makeDungeons(range(1, 8));
        $service  = $this->buildService(collect([3 => 4]));

        // Act
        $result = $service->getPopularDungeonIds($dungeons);

        // Assert
        $this->assertSame([3], $result->all());
    }

    #[Test]
    public function getPopularDungeonIds_givenViewsOfDungeonsOutsideTheList_ignoresThem(): void
    {
        // Arrange
        $dungeons = $this->makeDungeons(range(1, 4));
        $service  = $this->buildService(collect([1 => 5, 2 => 3, 99 => 1000]));

        // Act
        $result = $service->getPopularDungeonIds($dungeons);

        // Assert
        $this->assertSame([1], $result->all());
    }

    #[Test]
    public function getPopularDungeonIds_givenTheConfiguredWindow_readsTheViewsSinceItsFirstDay(): void
    {
        // Arrange
        config(['keystoneguru.page_views.popular_dungeons_days' => 14]);
        $since                   = null;
        $pageViewCountRepository = $this->createMockPublic(PageViewCountRepositoryInterface::class);
        $pageViewCountRepository->method('getViewsPerDungeon')->willReturnCallback(static function (Carbon $from) use (&$since): Collection {
            $since = $from;

            return collect();
        });
        $service = $this->buildServiceWith($pageViewCountRepository);

        // Act
        $service->getPopularDungeonIds($this->makeDungeons([1]));

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
