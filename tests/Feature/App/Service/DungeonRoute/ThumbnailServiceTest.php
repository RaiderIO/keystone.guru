<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\File;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Service\DungeonRoute\Logging\ThumbnailServiceLoggingInterface;
use App\Service\DungeonRoute\ThumbnailService;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('ThumbnailService')]
final class ThumbnailServiceTest extends PublicTestCase
{
    use ProvidesDungeon;

    private function buildService(ThumbnailServiceLoggingInterface $log): ThumbnailService
    {
        return new ThumbnailService(
            $this->createMockPublic(DungeonRouteRepositoryInterface::class),
            $log,
        );
    }

    #[Test]
    public function createThumbnail_givenLocalEnvironment_returnsNullWithoutGeneratingThumbnail(): void
    {
        // Arrange
        $originalEnv      = $this->app['env'];
        $this->app['env'] = 'local';

        $log = $this->createMockPublic(ThumbnailServiceLoggingInterface::class);
        $log->expects($this->once())->method('doCreateThumbnailSkippedLocalEnvironment');
        $log->expects($this->never())->method('doCreateThumbnailProcessStart');

        $service      = $this->buildService($log);
        $dungeonRoute = new DungeonRoute(['public_key' => 'TEST1234']);

        try {
            // Act
            $result = $service->createThumbnail($dungeonRoute, 0);

            // Assert
            $this->assertNull($result);
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    #[Test]
    public function createThumbnail_givenNonLocalEnvironment_doesNotSkipDueToEnvironmentGuard(): void
    {
        // Arrange
        $originalEnv      = $this->app['env'];
        $this->app['env'] = 'testing';

        $log = $this->createMockPublic(ThumbnailServiceLoggingInterface::class);
        $log->expects($this->never())->method('doCreateThumbnailSkippedLocalEnvironment');

        $service      = $this->buildService($log);
        $dungeonRoute = new DungeonRoute(['public_key' => 'TEST1234']);

        try {
            // Act
            // Falls through past the environment guard; fails further down since
            // there's no real dungeon/floor/puppeteer setup here, which is fine
            // — this test only asserts the guard itself isn't triggered.
            $service->createThumbnail($dungeonRoute, 0);
        } catch (\Throwable) {
            // Expected: the route has no dungeon/mapping relations to continue with.
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    #[Test]
    public function attachThumbnailToDungeonRoute_givenExistingThumbnailForNonFacadeFloor_deletesOldThumbnailBeforeAttachingNew(): void
    {
        // Arrange
        // The old thumbnail's file lives on s3_user_uploads; the new one is written to the
        // default disk (attachThumbnailToDungeonRoute uses config('filesystems.default')),
        // so fake both to keep the test off any real disk regardless of FILESYSTEM_DISK.
        Storage::fake('s3_user_uploads');
        Storage::fake(config('filesystems.default'));

        $dungeon        = $this->getDungeonWithNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $dungeon->floors()->where('facade', false)->first();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $oldThumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'custom'           => false,
        ]);
        $oldFile = File::create([
            'model_id'    => $oldThumbnail->id,
            'model_class' => DungeonRouteThumbnail::class,
            'disk'        => 's3_user_uploads',
            'path'        => '/thumbnails/old.jpg',
        ]);
        $oldThumbnail->update(['file_id' => $oldFile->id]);

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));
        $method  = new ReflectionMethod($service, 'attachThumbnailToDungeonRoute');

        try {
            // Act
            $newThumbnail = $method->invoke($service, $dungeonRoute, $floor->index, '/thumbnails/new.jpg', 'fake-image-bytes');

            // Assert
            $this->assertDatabaseMissing('dungeon_route_thumbnails', ['id' => $oldThumbnail->id]);
            $this->assertDatabaseMissing('files', ['id' => $oldFile->id]);
            $this->assertSame(
                1,
                DungeonRouteThumbnail::query()
                    ->where('dungeon_route_id', $dungeonRoute->id)
                    ->where('floor_id', $floor->id)
                    ->where('custom', false)
                    ->count(),
            );
        } finally {
            DungeonRouteThumbnail::where('dungeon_route_id', $dungeonRoute->id)->get()->each->delete();
            $dungeonRoute->delete();
        }
    }
}
