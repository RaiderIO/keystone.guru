<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use App\Features\DungeonRouteListRework;
use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('CardPoster')]
final class CardPosterTest extends PublicTestCase
{
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
