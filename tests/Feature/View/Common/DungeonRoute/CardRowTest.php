<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('CardRow')]
final class CardRowTest extends PublicTestCase
{
    #[Test]
    public function render_givenRank_returnsRowMarkupWithRank(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create();

        try {
            // Act
            $html = view('common.dungeonroute.cardrow', [
                'dungeonroute' => $dungeonroute,
                'rank'         => 7,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('card_dungeonroute leaderboard_row', $html);
            $this->assertStringContainsString('leaderboard_rank', $html);
            $this->assertStringContainsString('>7<', $html);
            $this->assertStringContainsString(e($dungeonroute->title), $html);
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
            $html = view('common.dungeonroute.cardrow', [
                'dungeonroute' => $dungeonroute,
                'rank'         => 1,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringNotContainsString('leaderboard_rating', $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenExactlyRequiredEnemyForces_hidesEnemyForcesWarning(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create();
        // Meeting the requirement exactly is a healthy 100% and must not surface a warning
        $dungeonroute->enemy_forces = $dungeonroute->mappingVersion->enemy_forces_required;
        $dungeonroute->save();

        try {
            // Act
            $html = view('common.dungeonroute.cardrow', [
                'dungeonroute' => $dungeonroute,
                'rank'         => 1,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringNotContainsString('leaderboard_enemy_forces', $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenFavoritesCount_returnsFavoritesStat(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create();
        $dungeonroute->setAttribute('favorites_count', 8);

        try {
            // Act
            $html = view('common.dungeonroute.cardrow', [
                'dungeonroute' => $dungeonroute,
                'rank'         => 1,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('leaderboard_favorites', $html);
            $this->assertStringContainsString('fa-heart', $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenZeroFavoritesCount_hidesFavoritesStat(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create();
        $dungeonroute->setAttribute('favorites_count', 0);

        try {
            // Act
            $html = view('common.dungeonroute.cardrow', [
                'dungeonroute' => $dungeonroute,
                'rank'         => 1,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringNotContainsString('leaderboard_favorites', $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenMissingFavoritesCountAttribute_hidesFavoritesStat(): void
    {
        // Arrange - a route loaded without withCount('favorites') has no favorites_count attribute
        $dungeonroute = DungeonRoute::factory()->create();

        try {
            // Act
            $html = view('common.dungeonroute.cardrow', [
                'dungeonroute' => $dungeonroute,
                'rank'         => 1,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringNotContainsString('leaderboard_favorites', $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenRouteWithKillZones_returnsPullGraphBarPerPull(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create();
        foreach ([1, 2, 3, 4] as $index) {
            KillZone::factory()->create([
                'dungeon_route_id' => $dungeonroute->id,
                'index'            => $index,
            ]);
        }

        try {
            // Act
            $html = view('common.dungeonroute.cardrow', [
                'dungeonroute' => $dungeonroute,
                'rank'         => 1,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('leaderboard_pull_graph', $html);
            $this->assertSame(4, substr_count($html, '<rect'));
        } finally {
            $dungeonroute->killZones()->delete();
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenRouteWithoutKillZones_hidesPullGraph(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create();

        try {
            // Act
            $html = view('common.dungeonroute.cardrow', [
                'dungeonroute' => $dungeonroute,
                'rank'         => 1,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringNotContainsString('leaderboard_pull_graph', $html);
        } finally {
            $dungeonroute->delete();
        }
    }
}
