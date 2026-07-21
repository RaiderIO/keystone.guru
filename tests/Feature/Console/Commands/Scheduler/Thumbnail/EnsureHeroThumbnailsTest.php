<?php

namespace Tests\Feature\Console\Commands\Scheduler\Thumbnail;

use App\Console\Commands\Scheduler\Thumbnail\EnsureHeroThumbnails;
use App\Jobs\ProcessRouteFloorThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('Thumbnail')]
final class EnsureHeroThumbnailsTest extends PublicTestCase
{
    #[Test]
    public function handle_givenCurrentSeasonRoutes_runsSuccessfullyAndQueuesOnlyHeroJobs(): void
    {
        // Arrange - the seeded test DB has a current season with dungeons and popular routes; faking the
        // queue keeps the command from actually rendering thumbnails
        Queue::fake();

        // Act
        $this->artisan(EnsureHeroThumbnails::class)->assertSuccessful();

        // Assert - at least one hero job was queued, and every queued job is Hero-variant (never Standard)
        Queue::assertPushed(ProcessRouteFloorThumbnail::class);
        Queue::assertPushed(ProcessRouteFloorThumbnail::class, $this->isHeroVariantJob());
        Queue::assertNotPushed(ProcessRouteFloorThumbnail::class, fn(ProcessRouteFloorThumbnail $job): bool => !$this->isHeroVariantJob()($job));
    }

    private function isHeroVariantJob(): callable
    {
        return fn(ProcessRouteFloorThumbnail $job): bool => (fn(): DungeonRouteThumbnailVariant => $this->variant)->call($job) === DungeonRouteThumbnailVariant::Hero;
    }
}
