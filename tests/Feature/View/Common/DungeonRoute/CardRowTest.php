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
    public function render_givenRatedRoute_showsRatingStars(): void
    {
        // Arrange
        $dungeonroute = DungeonRoute::factory()->create([
            'rating'       => 8,
            'rating_count' => 5,
        ]);

        try {
            // Act
            $html = view('common.dungeonroute.cardrow', [
                'dungeonroute' => $dungeonroute,
                'rank'         => 1,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('leaderboard_rating', $html);
            $this->assertStringContainsString('fa-star', $html);
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
            $html = view('common.dungeonroute.cardrow', [
                'dungeonroute' => $dungeonroute,
                'rank'         => 1,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('leaderboard_enemy_forces', $html);
            $this->assertStringContainsString(sprintf('%d%%', $dungeonroute->getEnemyForcesPercentage()), $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenEnemyForcesOverOneHundredAndFivePercent_showsEnemyForcesWarning(): void
    {
        // Arrange
        $mappingVersion = $this->mappingVersionRequiringEnemyForces();
        $dungeonroute   = $this->createRouteOn($mappingVersion, (int)ceil($mappingVersion->enemy_forces_required * 1.06));

        try {
            // Act
            $html = view('common.dungeonroute.cardrow', [
                'dungeonroute' => $dungeonroute,
                'rank'         => 1,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertGreaterThanOrEqual(105, $dungeonroute->getEnemyForcesPercentage());
            $this->assertStringContainsString('leaderboard_enemy_forces', $html);
        } finally {
            $dungeonroute->delete();
        }
    }

    #[Test]
    public function render_givenExactlyRequiredEnemyForces_hidesEnemyForcesWarning(): void
    {
        // Arrange - meeting the requirement exactly is a healthy 100% and must not surface a warning
        $mappingVersion = $this->mappingVersionRequiringEnemyForces();
        $dungeonroute   = $this->createRouteOn($mappingVersion, $mappingVersion->enemy_forces_required);

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
    public function render_givenRouteWithAPullGrantingEnemyForces_showsPullGraph(): void
    {
        // Arrange
        $dungeonroute = $this->createRouteWithAPullGrantingEnemyForces();

        try {
            // Act
            $html = view('common.dungeonroute.cardrow', [
                'dungeonroute' => $dungeonroute,
                'rank'         => 1,
                'cache'        => false,
            ])->render();

            // Assert
            $this->assertStringContainsString('leaderboard_pull_graph', $html);
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
            $this->assertStringNotContainsString('leaderboard_pull_graph', $html);
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
