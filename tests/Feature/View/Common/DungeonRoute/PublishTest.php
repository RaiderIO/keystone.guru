<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\PublishedState;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('DungeonRoute')]
final class PublishTest extends PublicTestCase
{
    #[Test]
    public function render_givenTeamPublishedRoute_listsEveryPublishedStateKeyAndSelectsTeam(): void
    {
        // Arrange
        $dungeonRoute = DungeonRoute::factory()->create([
            'published_state_id' => PublishedState::ALL[PublishedState::TEAM],
        ]);

        try {
            // Act
            $html = view('common.dungeonroute.publish', [
                'allPublishedStates' => PublishedState::query()->orderBy('id')->get(),
                'dungeonroute'       => $dungeonRoute->fresh(),
            ])->render();

            // Assert
            preg_match_all('/<option value="([^"]*)"/', $html, $optionValues);
            $this->assertSame(array_keys(PublishedState::ALL), $optionValues[1]);
            $this->assertMatchesRegularExpression('/<option value="team"[^>]*\sselected/', $html);
        } finally {
            $dungeonRoute->delete();
        }
    }
}
