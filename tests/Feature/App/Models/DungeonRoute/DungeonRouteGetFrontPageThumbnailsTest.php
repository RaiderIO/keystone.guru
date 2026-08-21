<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRoute')]
final class DungeonRouteGetFrontPageThumbnailsTest extends PublicTestCase
{
    private function createThumbnailFile(DungeonRoute $dungeonRoute, int $floorId, DungeonRouteThumbnailVariant $variant, string $path): File
    {
        $thumbnail = DungeonRouteThumbnail::create([
            'dungeon_route_id' => $dungeonRoute->id,
            'floor_id'         => $floorId,
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
    public function getFrontPageThumbnails_givenFrontPageThumbnail_returnsFrontPageThumbnails(): void
    {
        // Arrange
        Storage::fake(config('filesystems.default'));

        $dungeonRoute = DungeonRoute::factory()->create();
        $floorId      = $dungeonRoute->dungeon->floors->first()->id;

        $this->createThumbnailFile($dungeonRoute, $floorId, DungeonRouteThumbnailVariant::Standard, '/thumbnails/standard.jpg');
        $frontPageFile = $this->createThumbnailFile($dungeonRoute, $floorId, DungeonRouteThumbnailVariant::FrontPage, '/thumbnails/front_page.jpg');

        try {
            // Act
            $result = $dungeonRoute->fresh()->getFrontPageThumbnails();

            // Assert
            $this->assertCount(1, $result);
            $this->assertSame($frontPageFile->getURL(), $result->first()->getURL());
        } finally {
            $dungeonRoute->dungeonRouteThumbnails()->get()->each->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getFrontPageThumbnails_givenNoFrontPageThumbnail_returnsStandardThumbnails(): void
    {
        // Arrange
        Storage::fake(config('filesystems.default'));

        $dungeonRoute = DungeonRoute::factory()->create();
        $floorId      = $dungeonRoute->dungeon->floors->first()->id;

        $standardFile = $this->createThumbnailFile($dungeonRoute, $floorId, DungeonRouteThumbnailVariant::Standard, '/thumbnails/standard.jpg');

        try {
            // Act
            $result = $dungeonRoute->fresh()->getFrontPageThumbnails();

            // Assert
            $this->assertCount(1, $result);
            $this->assertSame($standardFile->getURL(), $result->first()->getURL());
        } finally {
            $dungeonRoute->dungeonRouteThumbnails()->get()->each->delete();
            $dungeonRoute->delete();
        }
    }

    #[Test]
    public function getFrontPageThumbnails_givenNoThumbnails_returnsEmptyCollection(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create();

        try {
            // Act
            $result = $dungeonRoute->fresh()->getFrontPageThumbnails();

            // Assert
            $this->assertCount(0, $result);
        } finally {
            $dungeonRoute->delete();
        }
    }
}
