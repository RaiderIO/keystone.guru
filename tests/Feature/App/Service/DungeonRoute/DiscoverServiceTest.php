<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DiscoverService')]
final class DiscoverServiceTest extends PublicTestCase
{
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
}
