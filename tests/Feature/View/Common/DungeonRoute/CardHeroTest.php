<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('CardHero')]
final class CardHeroTest extends PublicTestCase
{
    #[Test]
    public function render_givenRoute_returnsHeroMarkup(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create();

        try {
            // Act
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('card_dungeonroute hero', $html);
            $this->assertStringContainsString(e($dungeonroute->title), $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenArchetype_returnsArchetypeLabel(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create();

        try {
            // Act
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => 'pug_friendly',
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString(
                __('view_dungeonroute.discover.dungeon.overview.archetypes.pug_friendly.label'),
                $html,
            );
            $this->assertStringContainsString(
                __('view_dungeonroute.discover.dungeon.overview.archetypes.pug_friendly.description'),
                $html,
            );
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenNullArchetype_returnsTopCommunityRouteEyebrow(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create();

        try {
            // Act
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString(__('view_common.dungeonroute.cardhero.top_community_route'), $html);
        } finally {
            $dungeonroute->delete();
        }
    }
}
