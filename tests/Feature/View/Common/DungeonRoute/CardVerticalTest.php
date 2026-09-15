<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('CardVertical')]
final class CardVerticalTest extends PublicTestCase
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
    public function render_givenNoUseFrontPageThumbnail_usesStandardThumbnailUrl(): void
    {
        // Arrange
        Storage::fake(config('filesystems.default'));

        $dungeonroute = DungeonRoute::factory()->create();
        $floorId      = $dungeonroute->dungeon->floors->first()->id;

        $standardFile = $this->createThumbnailFile($dungeonroute, $floorId, DungeonRouteThumbnailVariant::Standard, '/thumbnails/standard.jpg');
        $this->createThumbnailFile($dungeonroute, $floorId, DungeonRouteThumbnailVariant::FrontPage, '/thumbnails/front_page.jpg');

        try {
            // Act
            $html = view('common.dungeonroute.cardvertical', [
                'dungeonroute'      => $dungeonroute->fresh(),
                'currentAffixGroup' => null,
                'tierAffixGroup'    => null,
                'cache'             => false,
            ])->render();

            // Assert - default (Find Routes page) behaviour is unaffected by the front-page variant existing
            $this->assertStringContainsString($standardFile->getURL(), $html);
        } finally {
            $dungeonroute->dungeonRouteThumbnails()->get()->each->delete();
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenFrontPageThumbnailAndUseFrontPageThumbnailTrue_usesFrontPageThumbnailUrl(): void
    {
        // Arrange
        Storage::fake(config('filesystems.default'));

        $dungeonroute = DungeonRoute::factory()->create();
        $floorId      = $dungeonroute->dungeon->floors->first()->id;

        $this->createThumbnailFile($dungeonroute, $floorId, DungeonRouteThumbnailVariant::Standard, '/thumbnails/standard.jpg');
        $frontPageFile = $this->createThumbnailFile($dungeonroute, $floorId, DungeonRouteThumbnailVariant::FrontPage, '/thumbnails/front_page.jpg');

        try {
            // Act
            $html = view('common.dungeonroute.cardvertical', [
                'dungeonroute'          => $dungeonroute->fresh(),
                'currentAffixGroup'     => null,
                'tierAffixGroup'        => null,
                'useFrontPageThumbnail' => true,
                'cache'                 => false,
            ])->render();

            // Assert
            $this->assertStringContainsString($frontPageFile->getURL(), $html);
        } finally {
            $dungeonroute->dungeonRouteThumbnails()->get()->each->delete();
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenNoFrontPageThumbnailAndUseFrontPageThumbnailTrue_fallsBackToStandardThumbnailUrl(): void
    {
        // Arrange
        Storage::fake(config('filesystems.default'));

        $dungeonroute = DungeonRoute::factory()->create();
        $floorId      = $dungeonroute->dungeon->floors->first()->id;

        $standardFile = $this->createThumbnailFile($dungeonroute, $floorId, DungeonRouteThumbnailVariant::Standard, '/thumbnails/standard.jpg');

        try {
            // Act
            $html = view('common.dungeonroute.cardvertical', [
                'dungeonroute'          => $dungeonroute->fresh(),
                'currentAffixGroup'     => null,
                'tierAffixGroup'        => null,
                'useFrontPageThumbnail' => true,
                'cache'                 => false,
            ])->render();

            // Assert
            $this->assertStringContainsString($standardFile->getURL(), $html);
        } finally {
            $dungeonroute->dungeonRouteThumbnails()->get()->each->delete();
            $dungeonroute->delete();
        }
    }
}
