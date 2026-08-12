<?php

namespace Tests\Feature\View\Common\DungeonRoute;

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
}
