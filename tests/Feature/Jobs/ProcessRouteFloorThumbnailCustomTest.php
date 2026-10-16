<?php

namespace Tests\Feature\Jobs;

use App\Jobs\Logging\ProcessRouteFloorThumbnailCustomLoggingInterface;
use App\Jobs\ProcessRouteFloorThumbnailCustom;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnailJob;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Jobs')]
#[Group('Thumbnail')]
final class ProcessRouteFloorThumbnailCustomTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function handle_givenMaxAttemptsReached_marksThumbnailJobAsError(): void
    {
        // Arrange
        [$dungeon]    = $this->findDungeon();
        $floor        = $dungeon->floors()->firstOrFail();
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
        ]);
        $thumbnailJob = DungeonRouteThumbnailJob::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'status'           => DungeonRouteThumbnailJob::STATUS_QUEUED,
        ]);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->never())->method('createThumbnailCustom');
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        $log = $this->createMockPublic(ProcessRouteFloorThumbnailCustomLoggingInterface::class);
        $log->expects($this->once())->method('handleMaxAttemptsReached');
        app()->instance(ProcessRouteFloorThumbnailCustomLoggingInterface::class, $log);

        try {
            // Act
            new ProcessRouteFloorThumbnailCustom(
                $thumbnailJob,
                $dungeonRoute,
                $floor->index,
                (int)config('keystoneguru.thumbnail.max_attempts'),
            )->handle();

            // Assert
            $this->assertSame(
                DungeonRouteThumbnailJob::STATUS_ERROR,
                DungeonRouteThumbnailJob::query()->whereKey($thumbnailJob->id)->value('status'),
            );
        } finally {
            DungeonRouteThumbnailJob::query()->whereKey($thumbnailJob->id)->delete();
            DungeonRoute::query()->whereKey($dungeonRoute->id)->delete();
        }
    }
}
