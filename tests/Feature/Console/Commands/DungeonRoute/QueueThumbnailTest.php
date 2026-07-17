<?php

namespace Tests\Feature\Console\Commands\DungeonRoute;

use App\Console\Commands\DungeonRoute\QueueThumbnail;
use App\Jobs\ProcessRouteFloorThumbnail;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('DungeonRoute')]
final class QueueThumbnailTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function handle_givenValidRoute_queuesAThumbnailJob(): void
    {
        // Arrange
        Queue::fake();

        $dungeon      = $this->getDungeonWithNonFacadeFloor();
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
        ]);

        try {
            // Act
            $this->artisan(QueueThumbnail::class, ['publicKey' => $dungeonRoute->public_key])->assertSuccessful();

            // Assert
            Queue::assertPushed(ProcessRouteFloorThumbnail::class);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function handle_givenUnknownPublicKey_failsWithoutQueueing(): void
    {
        // Arrange
        Queue::fake();

        // Act & Assert
        $this->artisan(QueueThumbnail::class, ['publicKey' => 'NOSUCHKEY'])->assertFailed();
        Queue::assertNothingPushed();
    }
}
