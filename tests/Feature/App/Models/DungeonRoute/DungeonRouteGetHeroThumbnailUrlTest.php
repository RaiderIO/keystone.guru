<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRoute')]
final class DungeonRouteGetHeroThumbnailUrlTest extends PublicTestCase
{
    private function createThumbnailFile(DungeonRoute $dungeonRoute, int $floorId, string $variant, string $path): File
    {
        $thumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floorId,
            'custom'           => false,
            'variant'          => $variant,
        ]);

        $file = File::create([
            'model_id'    => $thumbnail->id,
            'model_class' => DungeonRouteThumbnail::class,
            'disk'        => config('filesystems.default'),
            'path'        => $path,
        ]);
        $thumbnail->update(['file_id' => $file->id]);

        return $file;
    }

    #[Test]
    public function getHeroThumbnailUrl_givenHeroThumbnail_returnsHeroThumbnailUrl(): void
    {
        // Arrange
        Storage::fake(config('filesystems.default'));

        $dungeonRoute = DungeonRoute::factory()->create();
        $floorId      = $dungeonRoute->dungeon->floors->first()->id;

        $this->createThumbnailFile($dungeonRoute, $floorId, DungeonRouteThumbnail::VARIANT_STANDARD, '/thumbnails/standard.jpg');
        $heroFile = $this->createThumbnailFile($dungeonRoute, $floorId, DungeonRouteThumbnail::VARIANT_HERO, '/thumbnails/hero.jpg');

        try {
            // Act
            $result = $dungeonRoute->fresh()->getHeroThumbnailUrl();

            // Assert
            $this->assertSame($heroFile->getURL(), $result);
        } finally {
            $dungeonRoute->dungeonRouteThumbnails()->get()->each->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getHeroThumbnailUrl_givenNoHeroThumbnail_returnsStandardThumbnailUrl(): void
    {
        // Arrange
        Storage::fake(config('filesystems.default'));

        $dungeonRoute = DungeonRoute::factory()->create();
        $floorId      = $dungeonRoute->dungeon->floors->first()->id;

        $standardFile = $this->createThumbnailFile($dungeonRoute, $floorId, DungeonRouteThumbnail::VARIANT_STANDARD, '/thumbnails/standard.jpg');

        try {
            // Act
            $result = $dungeonRoute->fresh()->getHeroThumbnailUrl();

            // Assert
            $this->assertSame($standardFile->getURL(), $result);
        } finally {
            $dungeonRoute->dungeonRouteThumbnails()->get()->each->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getHeroThumbnailUrl_givenNoThumbnails_returnsNull(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create();

        try {
            // Act
            $result = $dungeonRoute->fresh()->getHeroThumbnailUrl();

            // Assert
            $this->assertNull($result);
        } finally {
            $dungeonRoute->delete();
        }
    }
}
