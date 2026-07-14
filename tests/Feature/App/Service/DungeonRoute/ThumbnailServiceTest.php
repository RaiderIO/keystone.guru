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
use Throwable;

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

    /**
     * @return array{0: mixed, 1: mixed} [originalEnv, originalDefaultDisk] to restore afterwards
     */
    private function setEnvAndDefaultDisk(string $env, string $disk): array
    {
        $original = [$this->app['env'], config('filesystems.default')];

        $this->app['env'] = $env;
        config(['filesystems.default' => $disk]);

        return $original;
    }

    /**
     * @param array{0: mixed, 1: mixed} $original
     */
    private function restoreEnvAndDefaultDisk(array $original): void
    {
        [$env, $disk] = $original;

        $this->app['env'] = $env;
        config(['filesystems.default' => $disk]);
    }

    #[Test]
    public function createThumbnail_givenLocalEnvironmentAndRemoteDisk_returnsNullWithoutGeneratingThumbnail(): void
    {
        // Arrange
        $original = $this->setEnvAndDefaultDisk('local', 's3_user_uploads');

        $log = $this->createMockPublic(ThumbnailServiceLoggingInterface::class);
        $log->expects($this->once())->method('doCreateThumbnailSkippedRemoteDiskFromLocal');
        $log->expects($this->never())->method('doCreateThumbnailProcessStart');

        $service      = $this->buildService($log);
        $dungeonRoute = new DungeonRoute(['public_key' => 'TEST1234']);

        try {
            // Act
            $result = $service->createThumbnail($dungeonRoute, 0);

            // Assert
            $this->assertNull($result);
        } finally {
            $this->restoreEnvAndDefaultDisk($original);
        }
    }

    #[Test]
    public function createThumbnail_givenLocalEnvironmentAndLocalDisk_doesNotSkipDueToDiskGuard(): void
    {
        // Arrange
        $original = $this->setEnvAndDefaultDisk('local', 'public');

        $log = $this->createMockPublic(ThumbnailServiceLoggingInterface::class);
        $log->expects($this->never())->method('doCreateThumbnailSkippedRemoteDiskFromLocal');

        $service      = $this->buildService($log);
        $dungeonRoute = new DungeonRoute(['public_key' => 'TEST1234']);

        try {
            // Act
            // Falls through past the disk-safety guard; fails further down since there's no
            // real dungeon/floor/puppeteer setup here, which is fine — this test only asserts
            // the guard itself isn't triggered.
            $service->createThumbnail($dungeonRoute, 0);
        } catch (Throwable) {
            // Expected: the route has no dungeon/mapping relations to continue with.
        } finally {
            $this->restoreEnvAndDefaultDisk($original);
        }
    }

    #[Test]
    public function isRemoteDiskUnsafeForLocalGeneration_givenLocalEnvironmentAndS3Disk_returnsTrue(): void
    {
        // Arrange
        $originalEnv      = $this->app['env'];
        $this->app['env'] = 'local';

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));
        $method  = new ReflectionMethod($service, 'isRemoteDiskUnsafeForLocalGeneration');

        try {
            // Act
            $result = $method->invoke($service, 's3_user_uploads');

            // Assert
            $this->assertTrue($result);
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    #[Test]
    public function isRemoteDiskUnsafeForLocalGeneration_givenLocalEnvironmentAndPublicDisk_returnsFalse(): void
    {
        // Arrange
        $originalEnv      = $this->app['env'];
        $this->app['env'] = 'local';

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));
        $method  = new ReflectionMethod($service, 'isRemoteDiskUnsafeForLocalGeneration');

        try {
            // Act
            $result = $method->invoke($service, 'public');

            // Assert
            $this->assertFalse($result);
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    #[Test]
    public function isRemoteDiskUnsafeForLocalGeneration_givenProductionEnvironmentAndS3Disk_returnsFalse(): void
    {
        // Arrange
        $originalEnv      = $this->app['env'];
        $this->app['env'] = 'production';

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));
        $method  = new ReflectionMethod($service, 'isRemoteDiskUnsafeForLocalGeneration');

        try {
            // Act
            $result = $method->invoke($service, 's3_user_uploads');

            // Assert
            $this->assertFalse($result);
        } finally {
            $this->app['env'] = $originalEnv;
        }
    }

    #[Test]
    public function getTargetFilePath_givenFolder_returnsPathWithoutLeadingSlash(): void
    {
        // Arrange
        $dungeonRoute = new DungeonRoute(['public_key' => 'TEST1234']);

        // Act
        $path = ThumbnailService::getTargetFilePath($dungeonRoute, 0, ThumbnailService::THUMBNAIL_FOLDER_PATH);

        // Assert
        $this->assertStringStartsNotWith('/', $path);
        $this->assertStringStartsWith('thumbnails/TEST1234/', $path);
    }

    #[Test]
    public function getPreviewUrl_givenNoPreviewBaseUrlConfigured_returnsAbsoluteRouteUrl(): void
    {
        // Arrange
        $originalBaseUrl = config('keystoneguru.thumbnail.preview_base_url');
        config(['keystoneguru.thumbnail.preview_base_url' => null]);

        $dungeon        = $this->getDungeonWithNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $dungeonRoute   = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));
        $method  = new ReflectionMethod($service, 'getPreviewUrl');

        try {
            // Act
            $url = $method->invoke($service, $dungeonRoute, 0, 1.0);

            // Assert
            $this->assertSame(route('dungeonroute.preview', [
                'dungeon'      => $dungeonRoute->dungeon,
                'dungeonroute' => $dungeonRoute->public_key,
                'title'        => $dungeonRoute->getTitleSlug(),
                'floorIndex'   => 0,
                'secret'       => config('keystoneguru.thumbnail.preview_secret'),
                'z'            => 1.0,
            ]), $url);
        } finally {
            config(['keystoneguru.thumbnail.preview_base_url' => $originalBaseUrl]);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getPreviewUrl_givenPreviewBaseUrlConfigured_returnsUrlPrefixedWithBase(): void
    {
        // Arrange
        $originalBaseUrl = config('keystoneguru.thumbnail.preview_base_url');
        config(['keystoneguru.thumbnail.preview_base_url' => 'http://nginx']);

        $dungeon        = $this->getDungeonWithNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $dungeonRoute   = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));
        $method  = new ReflectionMethod($service, 'getPreviewUrl');

        try {
            // Act
            $url = $method->invoke($service, $dungeonRoute, 0, 1.0);

            // Assert
            $expectedRelativeUrl = route('dungeonroute.preview', [
                'dungeon'      => $dungeonRoute->dungeon,
                'dungeonroute' => $dungeonRoute->public_key,
                'title'        => $dungeonRoute->getTitleSlug(),
                'floorIndex'   => 0,
                'secret'       => config('keystoneguru.thumbnail.preview_secret'),
                'z'            => 1.0,
            ], false);
            $this->assertSame(sprintf('http://nginx%s', $expectedRelativeUrl), $url);
        } finally {
            config(['keystoneguru.thumbnail.preview_base_url' => $originalBaseUrl]);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function attachThumbnailToDungeonRoute_givenExistingThumbnailForNonFacadeFloor_deletesOldThumbnailBeforeAttachingNew(): void
    {
        // Arrange
        // The old thumbnail's file lives on s3_user_uploads; the new one is written to the
        // disk passed in (mirroring what doCreateThumbnail resolves via
        // config('filesystems.default')), so fake both to keep the test off any real disk.
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
            'path'        => 'thumbnails/old.jpg',
        ]);
        $oldThumbnail->update(['file_id' => $oldFile->id]);

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));
        $method  = new ReflectionMethod($service, 'attachThumbnailToDungeonRoute');

        try {
            // Act
            $newThumbnail = $method->invoke(
                $service,
                $dungeonRoute,
                $floor->index,
                'thumbnails/new.jpg',
                'fake-image-bytes',
                config('filesystems.default'),
            );

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
