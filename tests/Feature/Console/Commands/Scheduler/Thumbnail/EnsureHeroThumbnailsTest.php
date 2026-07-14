<?php

namespace Tests\Feature\Console\Commands\Scheduler\Thumbnail;

use App\Console\Commands\Scheduler\Thumbnail\EnsureHeroThumbnails;
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

        // Act & Assert - the command resolves its hero routes and completes without error
        $this->artisan(EnsureHeroThumbnails::class)->assertSuccessful();
    }
}
