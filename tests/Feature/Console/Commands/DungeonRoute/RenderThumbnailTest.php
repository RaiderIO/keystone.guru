<?php

namespace Tests\Feature\Console\Commands\DungeonRoute;

use App\Console\Commands\DungeonRoute\RenderThumbnail;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\File;
use App\Models\Floor\Floor;
use App\Service\DungeonRoute\ThumbnailServiceInterface;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('DungeonRoute')]
final class RenderThumbnailTest extends PublicTestCase
{
    use ProvidesDungeon;

    #[Test]
    public function handle_givenProductionEnvironment_failsWithoutRendering(): void
    {
        // Arrange
        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->never())->method('createThumbnail');
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        $originalEnv      = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            // Act & Assert
            $this->artisan(RenderThumbnail::class, ['publicKey' => 'ANYKEY12'])->assertFailed();
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    #[Test]
    public function handle_givenUnknownPublicKey_failsWithoutRendering(): void
    {
        // Arrange
        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->never())->method('createThumbnail');
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        // Act & Assert
        $this->artisan(RenderThumbnail::class, ['publicKey' => 'NOSUCHKEY'])->assertFailed();
    }

    #[Test]
    public function handle_givenDiskOption_forcesDefaultFilesystemDiskBeforeRendering(): void
    {
        // Arrange
        $dungeon      = $this->getDungeonWithNonFacadeFloor();
        $floor        = $dungeon->floors()->where('facade', false)->first();
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
        ]);

        $capturedDisk     = null;
        $thumbnailService = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->once())
            ->method('createThumbnail')
            ->willReturnCallback(function () use (&$capturedDisk): DungeonRouteThumbnail {
                $capturedDisk = config('filesystems.default');

                // Return an unsaved model that looks file-backed, so the command reports success.
                $thumbnail = new DungeonRouteThumbnail();
                $thumbnail->setRelation('file', new File(['path' => 'thumbnails/x.jpg']));

                return $thumbnail;
            });
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        try {
            // Act
            $this->artisan(RenderThumbnail::class, [
                'publicKey' => $dungeonRoute->public_key,
                '--floor'   => $floor->index,
                '--disk'    => 'local',
            ])->assertSuccessful();

            // Assert
            $this->assertSame('local', $capturedDisk);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function handle_givenSuccessfulRender_doesNotPersistToTheSharedDatabase(): void
    {
        // Arrange
        $dungeon = $this->getDungeonWithNonFacadeFloor();
        /** @var Floor $floor */
        $floor        = $dungeon->floors()->where('facade', false)->first();
        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
        ]);

        // The fake render mirrors the real pipeline: it persists a thumbnail row AND writes the
        // rendered file to disk. The command's contract is that the DB write is rolled back while
        // the on-disk file (a non-transactional Storage write) survives for the agent to read.
        Storage::fake('local');

        $persistedThumbnailId = null;
        $renderedPath         = 'thumbnails/x.jpg';
        $thumbnailService     = $this->createMockPublic(ThumbnailServiceInterface::class);
        $thumbnailService->expects($this->once())
            ->method('createThumbnail')
            ->willReturnCallback(function () use ($dungeonRoute, $floor, $renderedPath, &$persistedThumbnailId): DungeonRouteThumbnail {
                $thumbnail = DungeonRouteThumbnail::create([
                    'dungeon_route_id' => $dungeonRoute->id,
                    'floor_id'         => $floor->id,
                    'custom'           => false,
                ]);
                $file = File::create([
                    'model_id'    => $thumbnail->id,
                    'model_class' => DungeonRouteThumbnail::class,
                    'disk'        => 'local',
                    'path'        => $renderedPath,
                ]);
                $thumbnail->update(['file_id' => $file->id]);

                Storage::disk('local')->put($renderedPath, 'fake-image-bytes');

                $persistedThumbnailId = $thumbnail->id;

                return $thumbnail;
            });
        app()->instance(ThumbnailServiceInterface::class, $thumbnailService);

        try {
            // Act
            $this->artisan(RenderThumbnail::class, [
                'publicKey' => $dungeonRoute->public_key,
                '--floor'   => $floor->index,
            ])->assertSuccessful();

            // Assert - the persisted thumbnail (and its file row) were rolled back...
            $this->assertNotNull($persistedThumbnailId);
            $this->assertDatabaseMissing('dungeon_route_thumbnails', ['id' => $persistedThumbnailId]);
            $this->assertSame(0, $dungeonRoute->dungeonRouteThumbnails()->count());
            // ...but the rendered file survives the rollback so the agent can read it.
            Storage::disk('local')->assertExists($renderedPath);
        } finally {
            DungeonRouteThumbnail::where('dungeon_route_id', $dungeonRoute->id)->get()->each->delete();
            $dungeonRoute->delete();
        }
    }
}
