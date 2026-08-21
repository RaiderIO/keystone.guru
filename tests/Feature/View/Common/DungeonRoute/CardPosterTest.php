<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use App\Features\DungeonRouteListRework;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteThumbnail;
use App\Models\DungeonRoute\DungeonRouteThumbnailVariant;
use App\Models\File;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('CardPoster')]
final class CardPosterTest extends PublicTestCase
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
    public function render_givenRoute_returnsPosterMarkupWithoutAffixes(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create();

        try {
            // Act
            $html = view('common.dungeonroute.cardposter', [
                'dungeonroute' => $dungeonroute,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('card_dungeonroute poster', $html);
            $this->assertStringContainsString(e($dungeonroute->title), $html);
            // Affixes are intentionally dropped from the poster card
            $this->assertStringNotContainsString('affix_toggle', $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenRoute_returnsTitleAttributeOnTitleLink(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create([
            'title' => 'A very long route title that the poster card clamps to two lines',
        ]);

        try {
            // Act
            $html = view('common.dungeonroute.cardposter', [
                'dungeonroute' => $dungeonroute,
                'cache'        => false,
            ])->render();

            // Assert - the visible title clamps to two lines, so the link carries the full text as a title attribute
            $this->assertStringContainsString(sprintf('title="%s"', e($dungeonroute->title)), $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenRatedRoute_returnsRatingStars(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create([
            'rating'       => 8,
            'rating_count' => 5,
        ]);

        try {
            // Act
            $html = view('common.dungeonroute.cardposter', [
                'dungeonroute' => $dungeonroute,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('poster_rating', $html);
            $this->assertStringContainsString('fa-star', $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenUnratedRoute_hidesRatingStars(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create([
            'rating'       => 0,
            'rating_count' => 0,
        ]);

        try {
            // Act
            $html = view('common.dungeonroute.cardposter', [
                'dungeonroute' => $dungeonroute,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringNotContainsString('poster_rating', $html);
        } finally {
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
            $html = view('common.dungeonroute.cardposter', [
                'dungeonroute'          => $dungeonroute->fresh(),
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
            $html = view('common.dungeonroute.cardposter', [
                'dungeonroute'          => $dungeonroute->fresh(),
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

    #[Test]
    public function cardlist_givenPosterOrientation_rendersPosterCard(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create();

        try {
            // Act
            $html = view('common.dungeonroute.cardlist', [
                'dungeonroutes'     => new Collection([$dungeonroute]),
                'currentAffixGroup' => null,
                'affixgroup'        => null,
                'orientation'       => 'poster',
                'cache'             => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('card_dungeonroute poster', $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function cardlist_givenVerticalOrientationAndFeatureActive_rendersPosterCard(): void
    {
        // Arrange
        Feature::define(DungeonRouteListRework::class, true);
        $dungeonroute = DungeonRoute::factory()->create();

        try {
            // Act
            $html = view('common.dungeonroute.cardlist', [
                'dungeonroutes'     => new Collection([$dungeonroute]),
                'currentAffixGroup' => null,
                'affixgroup'        => null,
                'orientation'       => 'vertical',
                'cache'             => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('card_dungeonroute poster', $html);
            $this->assertStringNotContainsString('card_dungeonroute vertical', $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function cardlist_givenVerticalOrientationAndFeatureInactive_rendersVerticalCard(): void
    {
        // Arrange
        Feature::define(DungeonRouteListRework::class, false);
        $dungeonroute = DungeonRoute::factory()->create();

        try {
            // Act
            $html = view('common.dungeonroute.cardlist', [
                'dungeonroutes'     => new Collection([$dungeonroute]),
                'currentAffixGroup' => null,
                'affixgroup'        => null,
                'orientation'       => 'vertical',
                'cache'             => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('card_dungeonroute vertical', $html);
            $this->assertStringNotContainsString('card_dungeonroute poster', $html);
        } finally {
            $dungeonroute->delete();
        }
    }
}
