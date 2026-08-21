<?php

namespace Tests\Feature\App\Jobs;

use App\Jobs\ProcessRouteFloorThumbnail;
use App\Models\DungeonRoute\DungeonRoute;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCases\PublicTestCase;

#[Group('Jobs')]
#[Group('Thumbnail')]
final class ProcessRouteFloorThumbnailTest extends PublicTestCase
{
    /**
     * The job does not use the queue worker's own retry mechanism - it re-dispatches itself so it can
     * carry its own $attempts counter past a `tries: 1` worker. That re-dispatch used to be immediate,
     * which burned every attempt within ~35 seconds and gave a momentarily slow environment no chance
     * to recover before the next try (#3920).
     *
     * @throws Exception
     */
    #[Test]
    #[DataProvider('failedRenderAttemptProvider')]
    public function handle_givenFailedRender_dispatchesRetryWithStaggeredDelay(int $attempts, int $expectedDelaySeconds): void
    {
        // Arrange
        Queue::fake();

        $dungeonRoute = $this->createDungeonRouteDueForThumbnail();

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('createThumbnail')->willReturn(null);
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        try {
            // Act
            new ProcessRouteFloorThumbnail($dungeonRoute, 1, true, $attempts)->handle();

            // Assert
            Queue::assertPushed(
                ProcessRouteFloorThumbnail::class,
                static function (ProcessRouteFloorThumbnail $job) use ($expectedDelaySeconds): bool {
                    return $job->delay instanceof Carbon
                        && (int)round($job->delay->diffInSeconds(Carbon::now(), true)) === $expectedDelaySeconds;
                },
            );
        } finally {
            $dungeonRoute->delete();
        }
    }

    /**
     * @return array<string, array{0: int, 1: int}>
     */
    public static function failedRenderAttemptProvider(): array
    {
        return [
            'first retry waits 10s'  => [0, 10],
            'second retry waits 60s' => [1, 60],
            'third retry waits 300s' => [2, 300],
        ];
    }

    /**
     * @throws Exception
     */
    #[Test]
    public function handle_givenSuccessfulRender_dispatchesNoRetry(): void
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
     * Attempts beyond the configured delays must not fall back to retrying immediately - raising
     * keystoneguru.thumbnail.max_attempts would otherwise silently reintroduce the tight retry loop.
     *
     * @throws Exception
     */
    #[Test]
    public function handle_givenAttemptBeyondConfiguredDelays_reusesTheLastDelay(): void
    {
        // Arrange
        Queue::fake();
        config(['keystoneguru.thumbnail.max_attempts' => 10]);

        $dungeonRoute = $this->createDungeonRouteDueForThumbnail();

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('createThumbnail')->willReturn(null);
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        try {
            // Act - attempts 5 is past the end of the [10, 60, 300] backoff array
            new ProcessRouteFloorThumbnail($dungeonRoute, 1, true, 5)->handle();

            // Assert
            Queue::assertPushed(
                ProcessRouteFloorThumbnail::class,
                static fn(ProcessRouteFloorThumbnail $job): bool => $job->delay instanceof Carbon
                    && (int)round($job->delay->diffInSeconds(Carbon::now(), true)) === 300,
            );
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
