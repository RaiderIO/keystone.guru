<?php

namespace Tests\Feature\Console\Commands\DungeonRoute;

use App\Console\Commands\DungeonRoute\MigrateThumbnailDisk;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('Console')]
#[Group('DungeonRoute')]
final class MigrateThumbnailDiskTest extends PublicTestCase
{
    use ProvidesDungeon;

    private function createThumbnailWithFile(string $disk, string $path): DungeonRouteThumbnail
    {
        $dungeon        = $this->getDungeonWithNonFacadeFloor();
        $mappingVersion = $dungeon->getCurrentMappingVersion();
        $floor          = $dungeon->floors()->where('facade', false)->first();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $mappingVersion->id,
        ]);

        $thumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floor->id,
            'custom'           => false,
        ]);
        $file = File::create([
            'model_id'    => $thumbnail->id,
            'model_class' => DungeonRouteThumbnail::class,
            'disk'        => $disk,
            'path'        => $path,
        ]);
        $thumbnail->update(['file_id' => $file->id]);

        return $thumbnail;
    }

    #[Test]
    public function handle_givenLocalDiskFileWithLeadingSlashPath_migratesToPublicDiskAndStripsSlash(): void
    {
        // Arrange
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('local')->put('thumbnails/KEY1234/file.jpg', 'fake-image-bytes');

        $thumbnail = $this->createThumbnailWithFile('local', '/thumbnails/KEY1234/file.jpg');

        try {
            // Act
            $this->artisan(MigrateThumbnailDisk::class)->assertSuccessful();

            // Assert
            $thumbnail->file->refresh();
            $this->assertSame('public', $thumbnail->file->disk);
            $this->assertSame('thumbnails/KEY1234/file.jpg', $thumbnail->file->path);
            Storage::disk('public')->assertExists('thumbnails/KEY1234/file.jpg');
        } finally {
            $thumbnail->delete();
            $thumbnail->dungeonRoute->delete();
        }
    }

    #[Test]
    public function handle_givenLocalDiskFileMissingFromDisk_skipsRowWithoutUpdating(): void
    {
        // Arrange
        Storage::fake('local');
        Storage::fake('public');

        $thumbnail = $this->createThumbnailWithFile('local', 'thumbnails/MISSING/file.jpg');

        try {
            // Act
            $this->artisan(MigrateThumbnailDisk::class)->assertSuccessful();

            // Assert
            $thumbnail->file->refresh();
            $this->assertSame('local', $thumbnail->file->disk);
            Storage::disk('public')->assertMissing('thumbnails/MISSING/file.jpg');
        } finally {
            $thumbnail->delete();
            $thumbnail->dungeonRoute->delete();
        }
    }

    #[Test]
    public function handle_givenProductionEnvironment_failsWithoutMigrating(): void
    {
        // Arrange
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('local')->put('thumbnails/KEY5678/file.jpg', 'fake-image-bytes');

        $thumbnail = $this->createThumbnailWithFile('local', 'thumbnails/KEY5678/file.jpg');

        $originalEnv      = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            // Act
            $this->artisan(MigrateThumbnailDisk::class)->assertFailed();

            // Assert
            $thumbnail->file->refresh();
            $this->assertSame('local', $thumbnail->file->disk);
            Storage::disk('public')->assertMissing('thumbnails/KEY5678/file.jpg');
        } finally {
            $this->app['env'] = $originalEnv;
            $thumbnail->delete();
            $thumbnail->dungeonRoute->delete();
        }
    }
}
