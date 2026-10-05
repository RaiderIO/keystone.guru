<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('Leaderboard')]
final class LeaderboardTest extends PublicTestCase
{
    #[Test]
    public function render_givenNoRoutes_returnsCreateFirstRouteCallToAction(): void
    {
        // Act
        $html = view('common.dungeonroute.leaderboard', [
            'dungeonroutes' => collect(),
            'cache'         => false,
        ])->render();

        // Assert - the empty state invites the visitor to act instead of dead-ending
        $this->assertStringContainsString(__('view_common.dungeonroute.cardlist.no_dungeonroutes'), $html);
        $this->assertStringContainsString(__('view_common.dungeonroute.leaderboard.create_first_route'), $html);
        $this->assertStringContainsString(route('dungeonroute.new'), $html);
    }

    #[Test]
    public function render_givenRoutes_ranksEveryRouteFromTheStartRankWithoutTheCallToAction(): void
    {
        // Arrange
        $dungeonroutes = collect();

        try {
            $dungeonroutes = DungeonRoute::factory()->count(2)->create();

            // Act
            $html = view('common.dungeonroute.leaderboard', [
                'dungeonroutes' => $dungeonroutes,
                'startRank'     => 4,
                'cache'         => false,
            ])->render();

            // Assert
            $this->assertSame(2, substr_count($html, 'card_dungeonroute leaderboard_row'));
            $this->assertMatchesRegularExpression('/leaderboard_rank[^"]*">4</', $html);
            $this->assertMatchesRegularExpression('/leaderboard_rank[^"]*">5</', $html);
            foreach ($dungeonroutes as $dungeonroute) {
                $this->assertStringContainsString(e($dungeonroute->title), $html);
            }
            $this->assertStringNotContainsString(__('view_common.dungeonroute.leaderboard.create_first_route'), $html);
        } finally {
            $dungeonroutes->each(static fn(DungeonRoute $dungeonroute) => $dungeonroute->delete());
        }
    }
}
