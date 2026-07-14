<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\KillZone\KillZone;
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

    #[Test]
    public function render_givenFavoritesCount_returnsFavoritesStat(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create();
        $dungeonroute->setAttribute('favorites_count', 12);

        try {
            // Act
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('hero_favorites', $html);
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
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringNotContainsString('hero_favorites', $html);
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
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringNotContainsString('hero_favorites', $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenRouteWithForcelessKillZones_hidesPullGraph(): void
    {
        // Arrange - kill zones without enemies grant no enemy forces and hold no boss, so they carry no information
        $dungeonroute = DungeonRoute::factory()->create();
        foreach ([1, 2, 3] as $index) {
            KillZone::factory()->create([
                'dungeon_route_id' => $dungeonroute->id,
                'index'            => $index,
            ]);
        }

        try {
            // Act
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringNotContainsString('hero_pull_graph', $html);
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
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringNotContainsString('hero_pull_graph', $html);
        } finally {
            $dungeonroute->delete();
        }
    }
}
