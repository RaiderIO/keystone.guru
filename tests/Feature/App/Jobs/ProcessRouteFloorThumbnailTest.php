<?php

namespace Tests\Feature\App\Jobs;

use App\Jobs\Logging\ProcessRouteFloorThumbnailLoggingInterface;
use App\Jobs\ProcessRouteFloorThumbnail;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Jobs')]
#[Group('Thumbnail')]
final class ProcessRouteFloorThumbnailTest extends PublicTestCase
{
    /**
     * A failed render throws out of handle() rather than re-dispatching itself, so the queue worker's
     * own retry mechanism applies $tries/backoff() - see those two below for what that configures.
     * Nothing here schedules a delay itself; without one, all $tries attempts would burn within ~35
     * seconds, giving a momentarily slow environment no chance to recover before the next try (#3920).
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function handle_givenFailedRender_throwsAndDoesNotReDispatchItself(): void
    {
        // Arrange
        Queue::fake();

        $dungeonRoute = $this->createDungeonRouteDueForThumbnail();

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('createThumbnail')->willReturn(null);
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        try {
            // Act
            $this->expectException(Exception::class);

            new ProcessRouteFloorThumbnail($dungeonRoute, 1, true)->handle();
        } finally {
            // Assert - the job never dispatches a copy of itself; the queue worker retries it instead
            Queue::assertNotPushed(ProcessRouteFloorThumbnail::class);

            $dungeonRoute->delete();
        }
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function handle_givenSuccessfulRender_doesNotThrow(): void
    {
        // Arrange
        Queue::fake();

        $dungeonRoute = $this->createDungeonRouteDueForThumbnail();

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('createThumbnail')->willReturn($dungeonRoute->dungeonRouteThumbnails()->make());
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        try {
            // Act
            new ProcessRouteFloorThumbnail($dungeonRoute, 1, true)->handle();

            // Assert
            Queue::assertNotPushed(ProcessRouteFloorThumbnail::class);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function construct_givenMaxAttemptsConfig_setsTries(): void
    {
        // Arrange
        config(['keystoneguru.thumbnail.max_attempts' => 5]);

        $dungeonRoute = $this->createDungeonRouteDueForThumbnail();

        try {
            // Act
            $job = new ProcessRouteFloorThumbnail($dungeonRoute, 1);

            // Assert - this is what makes the worker apply $tries attempts regardless of the thumbnail
            // Horizon supervisors' own `tries: 1` config; a job-level $tries always takes precedence.
            $this->assertSame(5, $job->tries);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function backoff_givenDefaults_returnsStaggeredDelays(): void
    {
        // Arrange
        $dungeonRoute = $this->createDungeonRouteDueForThumbnail();

        try {
            // Act
            $backoff = new ProcessRouteFloorThumbnail($dungeonRoute, 1)->backoff();

            // Assert
            $this->assertSame([10, 60, 300], $backoff);
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * failed() is what the queue worker calls once $tries is exhausted - this is the terminal-failure
     * diagnostic that used to be logged by a manually re-dispatched, immediately-no-op final attempt
     * (cold review finding on PR #4245); now it fires directly instead of occupying the queue.
     */
    #[Test]
    public function failed_givenException_logsMaxAttemptsReached(): void
    {
        // Arrange
        $dungeonRoute = $this->createDungeonRouteDueForThumbnail();

        $log = $this->createMockPublic(ProcessRouteFloorThumbnailLoggingInterface::class);
        $log->expects($this->once())->method('handleMaxAttemptsReached');
        app()->instance(ProcessRouteFloorThumbnailLoggingInterface::class, $log);

        try {
            // Act
            new ProcessRouteFloorThumbnail($dungeonRoute, 1, true)->failed(new Exception('render failed'));
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * A route whose thumbnail is considered out of date, so handle() actually attempts a render
     * rather than short-circuiting on handleThumbnailAlreadyUpToDate().
     */
    private function createDungeonRouteDueForThumbnail(): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'thumbnail_updated_at' => Carbon::now()->subDay(),
        ]);
    }
}
