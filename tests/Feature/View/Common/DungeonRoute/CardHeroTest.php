<?php

namespace Tests\Feature\View\Common\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\KillZone\KillZone;
use App\Models\KillZone\KillZoneEnemy;
use App\Models\Mapping\MappingVersion;
use App\Models\Npc\NpcClassification;
use Illuminate\Database\Query\JoinClause;
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
    public function render_givenRoute_returnsTitleAttributeOnTitleLink(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create([
            'title' => 'A very long route title that the hero card clamps to two lines',
        ]);

        try {
            // Act
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'cache'        => false,
            ])->render();

            // Assert - the visible title clamps to two lines, so the link carries the full text as a title attribute
            $this->assertStringContainsString(sprintf('title="%s"', e($dungeonroute->title)), $html);
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
    public function render_givenNullArchetypeAndHeroRank_returnsRankedCommunityRouteEyebrow(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create();

        try {
            // Act
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'heroRank'     => 2,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString(sprintf(__('view_common.dungeonroute.cardhero.ranked_community_route'), 2), $html);
            $this->assertStringNotContainsString(__('view_common.dungeonroute.cardhero.top_community_route'), $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenRatedRoute_showsRatingStars(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create([
            'rating'       => 8,
            'rating_count' => 5,
        ]);

        try {
            // Act
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('hero_rating', $html);
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
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringNotContainsString('hero_rating', $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenEnemyForcesBelowRequired_showsEnemyForcesWarning(): void
    {
        // Arrange
        $mappingVersion = $this->mappingVersionRequiringEnemyForces();
        $dungeonroute   = $this->createRouteOn($mappingVersion, $mappingVersion->enemy_forces_required - 1);

        try {
            // Act
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('hero_enemy_forces', $html);
            $this->assertStringContainsString(sprintf('%d%%', $dungeonroute->getEnemyForcesPercentage()), $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenExactlyRequiredEnemyForces_hidesEnemyForcesWarning(): void
    {
        // Arrange
        $mappingVersion = $this->mappingVersionRequiringEnemyForces();
        $dungeonroute   = $this->createRouteOn($mappingVersion, $mappingVersion->enemy_forces_required);

        try {
            // Act
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringNotContainsString('hero_enemy_forces', $html);
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
    public function render_givenRouteWithAPullGrantingEnemyForces_showsPullGraph(): void
    {
        // Arrange
        $dungeonroute = $this->createRouteWithAPullGrantingEnemyForces();

        try {
            // Act
            $html = view('common.dungeonroute.cardhero', [
                'dungeonroute' => $dungeonroute,
                'archetype'    => null,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('hero_pull_graph', $html);
            $this->assertSame(1, substr_count($html, '<rect'));
        } finally {
            $this->deleteRouteWithKillZones($dungeonroute);
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

    private function mappingVersionRequiringEnemyForces(): MappingVersion
    {
        return MappingVersion::query()->where('enemy_forces_required', '>', 0)->firstOrFail();
    }

    private function createRouteOn(MappingVersion $mappingVersion, int $enemyForces): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'dungeon_id'         => $mappingVersion->dungeon_id,
            'mapping_version_id' => $mappingVersion->id,
            'enemy_forces'       => $enemyForces,
        ]);
    }

    /**
     * A route with one pull holding one non-boss enemy that grants enemy forces on the route's mapping version.
     */
    private function createRouteWithAPullGrantingEnemyForces(): DungeonRoute
    {
        /** @var Enemy $enemy */
        $enemy = Enemy::query()
            ->select('enemies.*')
            ->join('npc_enemy_forces', static fn(JoinClause $join) => $join
                ->on('npc_enemy_forces.npc_id', '=', 'enemies.npc_id')
                ->on('npc_enemy_forces.mapping_version_id', '=', 'enemies.mapping_version_id'))
            ->join('npcs', 'npcs.id', '=', 'enemies.npc_id')
            ->whereNull('enemies.mdt_npc_id')
            ->whereNotNull('enemies.mdt_id')
            ->whereNull('enemies.enemy_forces_override')
            ->where('npc_enemy_forces.enemy_forces', '>', 0)
            ->whereNotIn('npcs.classification_id', [
                NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
                NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS],
            ])
            ->firstOrFail();

        $dungeonroute = $this->createRouteOn($enemy->mappingVersion, 0);
        $killZone     = KillZone::factory()->create([
            'dungeon_route_id' => $dungeonroute->id,
            'index'            => 1,
        ]);
        KillZoneEnemy::create([
            'kill_zone_id' => $killZone->id,
            'enemy_id'     => $enemy->id,
            'npc_id'       => $enemy->npc_id,
            'mdt_id'       => $enemy->mdt_id,
        ]);

        return $dungeonroute;
    }

    private function deleteRouteWithKillZones(DungeonRoute $dungeonroute): void
    {
        KillZoneEnemy::query()->whereIn('kill_zone_id', $dungeonroute->killZones()->pluck('id'))->delete();
        $dungeonroute->killZones()->delete();
        $dungeonroute->delete();
    }
}
