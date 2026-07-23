<?php

namespace Tests\Feature\Console\Commands\Scheduler\Thumbnail;

use App\Console\Commands\Scheduler\Thumbnail\EnsureHeroThumbnails;
use App\Jobs\ProcessRouteFloorThumbnail;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\Season;
use App\Service\DungeonRoute\DiscoverServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Thumbnail')]
final class EnsureHeroThumbnailsTest extends PublicTestCase
{
    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenHeroRoutes_queuesOnlyHeroVariantJobs(): void
    {
        // Arrange - explicitly mock the season/hero-route resolution rather than relying on ambient
        // seed data (a real environment can have zero current-season popular/weekly routes, which
        // would make this test vacuously pass or, worse, silently assert nothing)
        Queue::fake();

        $season       = Season::query()->firstOrFail();
        $dungeonRoute = DungeonRoute::factory()->create();

        $seasonService = $this->createMockPublic(SeasonServiceInterface::class);
        $seasonService->method('getCurrentSeason')->willReturn($season);
        app()->instance(SeasonServiceInterface::class, $seasonService);

        $discoverService = $this->createMockPublic(DiscoverServiceInterface::class);
        $discoverService->method('heroRoutes')->willReturn(collect([$dungeonRoute]));
        app()->instance(DiscoverServiceInterface::class, $discoverService);

        try {
            // Act
            $this->artisan(EnsureHeroThumbnails::class)->assertSuccessful();

            // Assert - the mocked hero route was queued, and every queued job is Hero-variant (never Standard)
            Queue::assertPushed(ProcessRouteFloorThumbnail::class);
            Queue::assertPushed(ProcessRouteFloorThumbnail::class, $this->isHeroVariantJob());
            Queue::assertNotPushed(ProcessRouteFloorThumbnail::class, fn(ProcessRouteFloorThumbnail $job): bool => !$this->isHeroVariantJob()($job));
        } finally {
            $dungeonRoute->delete();
        }
    }

    private function isHeroVariantJob(): callable
    {
        return fn(ProcessRouteFloorThumbnail $job): bool => (fn(): DungeonRouteThumbnailVariant => $this->variant)->call($job) === DungeonRouteThumbnailVariant::Hero;
    }
}
