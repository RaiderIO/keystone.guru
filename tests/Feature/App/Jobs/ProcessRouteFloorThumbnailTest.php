<?php

namespace Tests\Feature\App\Jobs;

use App\Exceptions\ThumbnailRenderFailedException;
use App\Jobs\Logging\ProcessRouteFloorThumbnailLoggingInterface;
use App\Jobs\ProcessRouteFloorThumbnail;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Service\DungeonRoute\ThumbnailGenerationToggleServiceInterface;
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

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function handle_givenFailedRenderWithAttemptsRemaining_rendersAsNonFinalAttemptAndThrowsUnreportedException(): void
    {
        // Arrange
        config(['keystoneguru.thumbnail.max_attempts' => 3]);

        $dungeonRoute = $this->createDungeonRouteDueForThumbnail();

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->once())
            ->method('createThumbnail')
            ->with($this->anything(), 1, 1, DungeonRouteThumbnailVariant::Standard, false)
            ->willReturn(null);
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        try {
            // Assert
            $this->expectException(ThumbnailRenderFailedException::class);

            // Act - outside a queue worker attempts() is 1
            new ProcessRouteFloorThumbnail($dungeonRoute, 1, true)->handle();
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function handle_givenLastAttempt_rendersAsFinalAttempt(): void
    {
        // Arrange
        config(['keystoneguru.thumbnail.max_attempts' => 1]);

        $dungeonRoute = $this->createDungeonRouteDueForThumbnail();

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->once())
            ->method('createThumbnail')
            ->with($this->anything(), 1, 1, DungeonRouteThumbnailVariant::Standard, true)
            ->willReturn($dungeonRoute->dungeonRouteThumbnails()->make());
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        try {
            // Act
            new ProcessRouteFloorThumbnail($dungeonRoute, 1, true)->handle();
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
     * A pause is meant to stop the render machinery entirely during a deploy, so a job that was already
     * on the queue when the pause went in must not render either.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function handle_givenGenerationPaused_doesNotCreateThumbnail(): void
    {
        // Arrange
        Queue::fake();

        $dungeonRoute = $this->createDungeonRouteDueForThumbnail();

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->never())->method('createThumbnail');
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        $toggleService = $this->createMockPublic(ThumbnailGenerationToggleServiceInterface::class);
        $toggleService->method('isPaused')->willReturn(true);
        app()->instance(ThumbnailGenerationToggleServiceInterface::class, $toggleService);

        $log = $this->createMockPublic(ProcessRouteFloorThumbnailLoggingInterface::class);
        $log->expects($this->once())->method('handleThumbnailGenerationPaused');
        app()->instance(ProcessRouteFloorThumbnailLoggingInterface::class, $log);

        try {
            // Act
            new ProcessRouteFloorThumbnail($dungeonRoute, 1, true)->handle();

            // Assert - no exception, so $tries is untouched and the job simply completes
            Queue::assertNotPushed(ProcessRouteFloorThumbnail::class);
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * A discarded job must not leave the route looking like a refresh is still pending: the repository
     * reads a marker newer than thumbnail_updated_at as "queued" and skips the route for
     * refresh_requeue_hours (72h), which would keep its thumbnail stale long after the pause is lifted.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function handle_givenGenerationPaused_rewindsTheQueueMarkerSoTheRouteIsRefreshedAfterResuming(): void
    {
        // Arrange
        Queue::fake();

        $dungeonRoute = $this->createDungeonRouteDueForThumbnail();
        DungeonRoute::query()->whereKey($dungeonRoute->id)->toBase()->update([
            'thumbnail_refresh_queued_at' => Carbon::now()->toDateTimeString(),
        ]);
        $updatedAt = $dungeonRoute->refresh()->updated_at->toDateTimeString();

        $toggleService = $this->createMockPublic(ThumbnailGenerationToggleServiceInterface::class);
        $toggleService->method('isPaused')->willReturn(true);
        app()->instance(ThumbnailGenerationToggleServiceInterface::class, $toggleService);

        try {
            // Act
            new ProcessRouteFloorThumbnail($dungeonRoute, 1, true)->handle();

            // Assert
            $dungeonRoute->refresh();
            $this->assertSame('1970-01-01 00:00:00', $dungeonRoute->thumbnail_refresh_queued_at->toDateTimeString());
            $this->assertSame($updatedAt, $dungeonRoute->updated_at->toDateTimeString());
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * Only the standard refresh records the marker, so a hero job has none of its own to rewind - and
     * rewinding it anyway would wrongly make the route's standard thumbnail look due.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function handle_givenGenerationPausedAndHeroVariant_leavesTheQueueMarkerAlone(): void
    {
        // Arrange
        Queue::fake();

        $dungeonRoute = $this->createDungeonRouteDueForThumbnail();
        $queuedAt     = Carbon::now()->startOfSecond()->toDateTimeString();
        DungeonRoute::query()->whereKey($dungeonRoute->id)->toBase()->update([
            'thumbnail_refresh_queued_at' => $queuedAt,
        ]);

        $toggleService = $this->createMockPublic(ThumbnailGenerationToggleServiceInterface::class);
        $toggleService->method('isPaused')->willReturn(true);
        app()->instance(ThumbnailGenerationToggleServiceInterface::class, $toggleService);

        try {
            // Act
            new ProcessRouteFloorThumbnail($dungeonRoute, 1, true, DungeonRouteThumbnailVariant::Hero)->handle();

            // Assert
            $this->assertSame($queuedAt, $dungeonRoute->refresh()->thumbnail_refresh_queued_at->toDateTimeString());
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
