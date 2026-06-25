<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('DungeonRouteSaveService')]
final class DungeonRouteSaveServiceCloneTest extends DungeonRouteSaveServiceTestCase
{
    #[Test]
    public function cloneRoute_givenSourceRoute_createsRouteWithCloneOfAndNullTeamId(): void
    {
        // Arrange — cloneRoute uses Auth::id() for author_id which is NOT NULL in the DB
        Auth::loginUsingId(1);

        $source = DungeonRoute::factory()->create(['team_id' => null]);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('copyThumbnails')->willReturn(null);

        $service = $this->buildService(thumbnailService: $thumbnailService);
        $clone   = null;

        try {
            // Act
            $clone = $service->cloneRoute($source);

            // Assert
            $this->assertTrue($clone->exists);
            $this->assertEquals($source->public_key, $clone->clone_of);
            $this->assertNull($clone->team_id);
            $this->assertNotEquals($source->public_key, $clone->public_key);
            $this->assertEquals($source->dungeon_id, $clone->dungeon_id);
        } finally {
            Auth::logout();
            if ($clone?->id !== null) {
                $this->cleanupRoute($clone);
            }
            $this->cleanupRoute($source);
        }
    }

    #[Test]
    public function cloneRoute_givenUnpublishedTrue_setsUnpublishedPublishedState(): void
    {
        // Arrange
        Auth::loginUsingId(1);

        $source = DungeonRoute::factory()->create([
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('copyThumbnails')->willReturn(null);

        $service = $this->buildService(thumbnailService: $thumbnailService);
        $clone   = null;

        try {
            // Act
            $clone = $service->cloneRoute($source, true);

            // Assert
            $this->assertEquals(
                PublishedState::ALL[PublishedState::UNPUBLISHED],
                $clone->published_state_id,
                'Cloning with unpublished=true must set published_state_id to UNPUBLISHED',
            );
        } finally {
            Auth::logout();
            if ($clone?->id !== null) {
                $this->cleanupRoute($clone);
            }
            $this->cleanupRoute($source);
        }
    }

    #[Test]
    public function cloneRoute_givenUnpublishedFalse_copiesSourcePublishedState(): void
    {
        // Arrange
        Auth::loginUsingId(1);

        $source = DungeonRoute::factory()->create([
            'published_state_id' => PublishedState::ALL[PublishedState::WORLD],
        ]);

        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->method('copyThumbnails')->willReturn(null);

        $service = $this->buildService(thumbnailService: $thumbnailService);
        $clone   = null;

        try {
            // Act
            $clone = $service->cloneRoute($source, false);

            // Assert
            $this->assertEquals(
                PublishedState::ALL[PublishedState::WORLD],
                $clone->published_state_id,
                sprintf(
                    'Cloning with unpublished=false must copy published_state_id from source (%d)',
                    PublishedState::ALL[PublishedState::WORLD],
                ),
            );
        } finally {
            Auth::logout();
            if ($clone?->id !== null) {
                $this->cleanupRoute($clone);
            }
            $this->cleanupRoute($source);
        }
    }
}
