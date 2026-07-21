<?php

namespace Tests\Feature\App\Service\DungeonRoute;

use App\Jobs\ProcessRouteFloorThumbnail;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\File;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteThumbnailRepositoryInterface;
use App\Service\DungeonRoute\Logging\ThumbnailServiceLoggingInterface;
use App\Service\DungeonRoute\ThumbnailService;
use Illuminate\Support\Facades\Queue;
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
            // Real (not mocked) - the freshness tests exercise its actual DB query against seeded thumbnails
            app()->make(DungeonRouteThumbnailRepositoryInterface::class),
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
    public function createThumbnail_givenLocalEnvironmentAndRemoteDisk_logsRedirectToPublicDisk(): void
    {
        // Arrange
        $original = $this->setEnvAndDefaultDisk('local', 's3_user_uploads');

        $log = $this->createMockPublic(ThumbnailServiceLoggingInterface::class);
        $log->expects($this->once())->method('doCreateThumbnailRedirectedRemoteDiskFromLocal')->with('s3_user_uploads');

        $service      = $this->buildService($log);
        $dungeonRoute = new DungeonRoute(['public_key' => 'TEST1234']);

        try {
            // Act
            // Falls through past the disk-safety guard onto the public disk instead of returning
            // null; fails further down since there's no real dungeon/floor/puppeteer setup here,
            // which is fine - this test only asserts that the redirect decision was made and
            // logged. The actual write landing on the public disk (not S3) is covered separately
            // by attachThumbnailToDungeonRoute_givenLocalEnvironmentAndOldThumbnailOnRemoteDisk_leavesTheRemoteFileUntouched.
            $service->createThumbnail($dungeonRoute, 0);
        } catch (Throwable) {
            // Expected: the route has no dungeon/mapping relations to continue with.
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
        $log->expects($this->never())->method('doCreateThumbnailRedirectedRemoteDiskFromLocal');

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
                // The default (standard) variant thickens the killzone-path lines by its config multiplier
                'killzonepathweight' => 3,
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
                // The default (standard) variant thickens the killzone-path lines by its config multiplier
                'killzonepathweight' => 3,
            ], false);
            $this->assertSame(sprintf('http://nginx%s', $expectedRelativeUrl), $url);
        } finally {
            config(['keystoneguru.thumbnail.preview_base_url' => $originalBaseUrl]);
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getPreviewUrl_givenHeroVariant_omitsKillZonePathWeightParam(): void
    {
        // Arrange - the hero variant keeps normal-width lines (config multiplier is null), so no param is added
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
            $url = $method->invoke($service, $dungeonRoute, 0, 1.0, DungeonRouteThumbnailVariant::Hero);

            // Assert - the hero render carries no killzonepathweight override
            $this->assertStringNotContainsString('killzonepathweight', $url);
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

    #[Test]
    public function attachThumbnailToDungeonRoute_givenExistingStandardThumbnail_keepsItWhenAttachingHeroVariant(): void
    {
        // Arrange
        Storage::fake('s3_user_uploads');
        Storage::fake(config('filesystems.default'));

        $dungeon        = $this->getDungeonWithNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $dungeon->floors()->where('facade', false)->first();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $standardThumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'custom'           => false,
            'variant'          => DungeonRouteThumbnailVariant::Standard,
        ]);
        $standardFile = File::create([
            'model_id'    => $standardThumbnail->id,
            'model_class' => DungeonRouteThumbnail::class,
            'disk'        => 's3_user_uploads',
            'path'        => '/thumbnails/standard.jpg',
        ]);
        $standardThumbnail->update(['file_id' => $standardFile->id]);

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));
        $method  = new ReflectionMethod($service, 'attachThumbnailToDungeonRoute');

        try {
            // Act - attach a hero variant; the standard thumbnail must be left untouched
            $method->invoke($service, $dungeonRoute, $floor->index, '/thumbnails/hero.jpg', 'fake-image-bytes', config('filesystems.default'), DungeonRouteThumbnailVariant::Hero);

            // Assert - both variants now coexist
            $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $standardThumbnail->id]);
            $this->assertSame(
                1,
                DungeonRouteThumbnail::query()
                    ->where('dungeon_route_id', $dungeonRoute->id)
                    ->where('variant', DungeonRouteThumbnailVariant::Standard)
                    ->count(),
            );
            $this->assertSame(
                1,
                DungeonRouteThumbnail::query()
                    ->where('dungeon_route_id', $dungeonRoute->id)
                    ->where('variant', DungeonRouteThumbnailVariant::Hero)
                    ->count(),
            );
        } finally {
            DungeonRouteThumbnail::where('dungeon_route_id', $dungeonRoute->id)->get()->each->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function attachThumbnailToDungeonRoute_givenLocalEnvironmentAndOldThumbnailOnRemoteDisk_leavesTheRemoteFileUntouched(): void
    {
        // Arrange - regression guard: a local environment restoring a production database backup
        // can have thumbnail File rows that still point at the real s3_user_uploads disk.
        // Attaching a new thumbnail DB-deletes the superseded old one, but must never physically
        // delete that S3 object - it's a read-only backup, not something local writes may mutate.
        $originalEnv      = $this->app['env'];
        $this->app['env'] = 'local';
        Storage::fake('s3_user_uploads');
        Storage::fake('public');

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
        Storage::disk('s3_user_uploads')->put('thumbnails/old.jpg', 'prod-backup-bytes');
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
            $method->invoke($service, $dungeonRoute, $floor->index, 'thumbnails/new.jpg', 'fake-image-bytes', 'public');

            // Assert - the old DB rows are gone...
            $this->assertDatabaseMissing('dungeon_route_thumbnails', ['id' => $oldThumbnail->id]);
            $this->assertDatabaseMissing('files', ['id' => $oldFile->id]);
            // ...but the real S3 object it pointed at was never touched
            Storage::disk('s3_user_uploads')->assertExists('thumbnails/old.jpg');
        } finally {
            $this->app['env'] = $originalEnv;
            DungeonRouteThumbnail::where('dungeon_route_id', $dungeonRoute->id)->get()->each->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function attachThumbnailToDungeonRoute_givenCustomVariant_dualWritesCustomFlagAndKeepsOtherVariants(): void
    {
        // Arrange
        Storage::fake('s3_user_uploads');
        Storage::fake(config('filesystems.default'));

        $dungeon        = $this->getDungeonWithNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $dungeon->floors()->where('facade', false)->first();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        // A pre-existing standard thumbnail must survive a custom render (custom renders never delete others).
        $standardThumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'custom'           => false,
            'variant'          => DungeonRouteThumbnailVariant::Standard,
        ]);

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));
        $method  = new ReflectionMethod($service, 'attachThumbnailToDungeonRoute');

        try {
            // Act - attach a custom variant
            $customThumbnail = $method->invoke($service, $dungeonRoute, $floor->index, '/thumbnails_custom/custom.jpg', 'fake-image-bytes', config('filesystems.default'), DungeonRouteThumbnailVariant::Custom);

            // Assert - variant is 'custom' and the legacy custom boolean is dual-written to true
            $this->assertSame(DungeonRouteThumbnailVariant::Custom, $customThumbnail->variant);
            $this->assertTrue((bool)$customThumbnail->custom);
            // The pre-existing standard thumbnail is untouched
            $this->assertDatabaseHas('dungeon_route_thumbnails', ['id' => $standardThumbnail->id]);
        } finally {
            DungeonRouteThumbnail::where('dungeon_route_id', $dungeonRoute->id)->get()->each->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function queueThumbnailRefresh_givenHeroVariantAndNoHeroThumbnail_queuesAHeroJob(): void
    {
        // Arrange
        Queue::fake();
        $dungeon        = $this->getDungeonWithNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $dungeonRoute   = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));

        try {
            // Act
            $result = $service->queueThumbnailRefresh($dungeonRoute, false, DungeonRouteThumbnailVariant::Hero);

            // Assert
            $this->assertTrue($result);
            Queue::assertPushed(ProcessRouteFloorThumbnail::class);
        } finally {
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function queueThumbnailRefresh_givenHeroVariantAndFreshHeroThumbnail_doesNotQueue(): void
    {
        // Arrange - a dungeon with exactly one non-facade floor, so the freshness check's expected-floor-
        // count (one thumbnail == one floor) doesn't flake on dungeons with several floors.
        Queue::fake();
        $dungeon        = $this->getDungeonWithExactlyOneNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $dungeon->floors()->where('facade', false)->first();
        $dungeonRoute   = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        // A hero thumbnail rendered after the route's last content change is fresh. Timestamps are on, so
        // pin updated_at via a query-builder update (the freshness check reads it straight from the DB).
        $heroThumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'custom'           => false,
            'variant'          => DungeonRouteThumbnailVariant::Hero,
        ]);
        DungeonRouteThumbnail::where('id', $heroThumbnail->id)
            ->update(['updated_at' => $dungeonRoute->updated_at->copy()->addMinute()]);

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));

        try {
            // Act
            $result = $service->queueThumbnailRefresh($dungeonRoute, false, DungeonRouteThumbnailVariant::Hero);

            // Assert
            $this->assertFalse($result);
            Queue::assertNothingPushed();
        } finally {
            $heroThumbnail->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function queueThumbnailRefresh_givenHeroVariantAndStaleHeroThumbnail_queuesAHeroJob(): void
    {
        // Arrange
        Queue::fake();
        $dungeon        = $this->getDungeonWithExactlyOneNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $dungeon->floors()->where('facade', false)->first();
        $dungeonRoute   = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        // A hero rendered before the route's last content change is stale and must be regenerated. Pin the
        // timestamp via a query-builder update since model timestamps would otherwise force it to now().
        $heroThumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'custom'           => false,
            'variant'          => DungeonRouteThumbnailVariant::Hero,
        ]);
        DungeonRouteThumbnail::where('id', $heroThumbnail->id)
            ->update(['updated_at' => $dungeonRoute->updated_at->copy()->subDay()]);

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));

        try {
            // Act
            $result = $service->queueThumbnailRefresh($dungeonRoute, false, DungeonRouteThumbnailVariant::Hero);

            // Assert
            $this->assertTrue($result);
            Queue::assertPushed(ProcessRouteFloorThumbnail::class);
        } finally {
            $heroThumbnail->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function queueThumbnailRefresh_givenHeroVariantAndOneOfMultipleFloorsMissingThumbnail_queuesAHeroJob(): void
    {
        // Arrange - a multi-floor route where every floor except one already has a fresh hero thumbnail.
        // The wholly-missing floor has no row at all (not just a stale one), so it must still trigger a
        // requeue rather than being masked by the other fresh floors.
        Queue::fake();
        $dungeon        = $this->getDungeonWithMultipleNonFacadeFloors();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floors         = $dungeon->floors()->where('facade', false)->where('active', true)->get();
        $dungeonRoute   = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $heroThumbnails = $floors->slice(0, -1)->map(function ($floor) use ($dungeonRoute) {
            $thumbnail = DungeonRouteThumbnail::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $floor->id,
                'custom'           => false,
                'variant'          => DungeonRouteThumbnailVariant::Hero,
            ]);
            DungeonRouteThumbnail::where('id', $thumbnail->id)
                ->update(['updated_at' => $dungeonRoute->updated_at->copy()->addMinute()]);

            return $thumbnail;
        });

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));

        try {
            // Act
            $result = $service->queueThumbnailRefresh($dungeonRoute, false, DungeonRouteThumbnailVariant::Hero);

            // Assert
            $this->assertTrue($result);
            Queue::assertPushed(ProcessRouteFloorThumbnail::class, $floors->count());
        } finally {
            $heroThumbnails->each->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function attachThumbnailToDungeonRoute_givenFacadeFloorWithExistingThumbnailsOnMultipleFloors_yieldsSingleFileBackedFacadeThumbnail(): void
    {
        // Arrange
        // Regression guard for #3580: a facade regeneration deletes every existing thumbnail of the
        // route and must always leave behind exactly one thumbnail that is backed by a File row
        // (a fileless thumbnail 403s because has_thumbnail stays true but nothing is on disk).
        Storage::fake('s3_user_uploads');
        Storage::fake(config('filesystems.default'));

        $dungeon        = $this->getDungeonWithFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $facadeFloor    = $dungeon->floors()->where('facade', true)->first();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        // Seed pre-existing thumbnails on every active floor (this is the state a route ends up in
        // when thumbnails were generated per-floor before a facade regeneration runs).
        $existingThumbnails = collect();
        foreach ($dungeon->floors()->active()->get() as $index => $floor) {
            $thumbnail = DungeonRouteThumbnail::create([
                'dungeon_route_id' => $dungeonRoute->id,
                'floor_id'         => $floor->id,
                'custom'           => false,
            ]);
            $file = File::create([
                'model_id'    => $thumbnail->id,
                'model_class' => DungeonRouteThumbnail::class,
                'disk'        => 's3_user_uploads',
                'path'        => sprintf('thumbnails/existing_%d.jpg', $index),
            ]);
            $thumbnail->update(['file_id' => $file->id]);
            $existingThumbnails->push($thumbnail);
        }

        $service = $this->buildService($this->createMockPublic(ThumbnailServiceLoggingInterface::class));
        $method  = new ReflectionMethod($service, 'attachThumbnailToDungeonRoute');

        try {
            // Act
            $newThumbnail = $method->invoke(
                $service,
                $dungeonRoute,
                $facadeFloor->index,
                'thumbnails/facade_new.jpg',
                'fake-image-bytes',
                config('filesystems.default'),
            );

            // Assert - the new thumbnail is file-backed
            $this->assertNotNull($newThumbnail->file_id);
            $this->assertNotNull($newThumbnail->file);
            Storage::disk(config('filesystems.default'))->assertExists('thumbnails/facade_new.jpg');

            // Assert - every pre-existing thumbnail (and its File) was removed
            foreach ($existingThumbnails as $existingThumbnail) {
                $this->assertDatabaseMissing('dungeon_route_thumbnails', ['id' => $existingThumbnail->id]);
                $this->assertDatabaseMissing('files', ['id' => $existingThumbnail->file_id]);
            }

            // Assert - the route is left with exactly one thumbnail, and it is file-backed
            $remaining = DungeonRouteThumbnail::query()
                ->where('dungeon_route_id', $dungeonRoute->id)
                ->get();
            $this->assertCount(1, $remaining);
            $this->assertNotNull($remaining->first()->file);
            $this->assertTrue($dungeonRoute->fresh()->has_thumbnail);
        } finally {
            DungeonRouteThumbnail::where('dungeon_route_id', $dungeonRoute->id)->get()->each->delete();
            $dungeonRoute->delete();
        }
    }
}
