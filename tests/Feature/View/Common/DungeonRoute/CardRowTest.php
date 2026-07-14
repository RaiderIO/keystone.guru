<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
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
}
