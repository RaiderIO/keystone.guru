<?php

namespace Tests\Feature\App\Models\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

#[Group('DungeonRoute')]
final class DungeonRouteGetFrontPageThumbnailsTest extends PublicTestCase
{
    use ProvidesDungeon;

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
    public function getFrontPageThumbnails_givenFrontPageThumbnailForOnlySomeFloors_fallsBackPerFloorToStandard(): void
    {
        // Arrange - a route generated mid-rollout: floor 1's front-page render landed, floor 2's is
        // still queued (or failed), so only a standard thumbnail exists for it. The carousel must
        // still show both floors, not drop floor 2 entirely (that was the bug found in cold review).
        Storage::fake(config('filesystems.default'));

        $dungeon           = $this->getDungeonWithMultipleNonFacadeFloors();
        [$floor1, $floor2] = $dungeon->floors()->active()->orderBy('index')->get()->all();

        $dungeonRoute = DungeonRoute::factory()->create([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
        ]);

        $frontPageFileFloor1 = $this->createThumbnailFile($dungeonRoute, $floor1->id, DungeonRouteThumbnailVariant::FrontPage, '/thumbnails/front_page_1.jpg');
        $standardFileFloor1  = $this->createThumbnailFile($dungeonRoute, $floor1->id, DungeonRouteThumbnailVariant::Standard, '/thumbnails/standard_1.jpg');
        $standardFileFloor2  = $this->createThumbnailFile($dungeonRoute, $floor2->id, DungeonRouteThumbnailVariant::Standard, '/thumbnails/standard_2.jpg');

        try {
            // Act
            $result = $dungeonRoute->fresh()->getFrontPageThumbnails();

            // Assert - floor 1 prefers its front-page render, floor 2 falls back to its standard one
            $urls = $result->map->getURL()->all();
            $this->assertContains($frontPageFileFloor1->getURL(), $urls);
            $this->assertNotContains($standardFileFloor1->getURL(), $urls);
            $this->assertContains($standardFileFloor2->getURL(), $urls);
            $this->assertCount(2, $result);
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
