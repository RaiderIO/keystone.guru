<?php

namespace Tests\Feature\App\Service\CombatLog\Builders;

use App\Dto\Request\CombatLog\Route\CombatLogRouteCoordRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteNpcRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Logic\Structs\LatLng;
use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\EnemyPatrol;
use App\Models\Npc\NpcClassification;
use App\Models\Polyline;
use App\Service\CombatLog\Builders\CombatLogRouteEnemyRecordingsBuilder;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Exercises the enemy resolution half of CombatLogRouteEnemyRecordingsBuilder::buildAndSave(): the rows recorded for
 * npcs that DID resolve to a mapped enemy, but only from far away. As with the enemy failure tests next to it the
 * builder is called directly rather than through the full API/builder pipeline, so the distance under test is the one
 * the test hands in rather than one the matcher happened to produce.
 */
#[Group('CombatLog')]
#[Group('CombatLogRouteEnemyRecordingsBuilder')]
final class CombatLogRouteEnemyRecordingsBuilderResolutionsTest extends PublicTestCase
{
    /** Comfortably above the configured recording threshold, whatever it is tuned to. */
    private const float FAR_DISTANCE = 100.0;

    #[Test]
    public function buildAndSave_givenMatchFurtherAwayThanThreshold_recordsTheResolution(): void
    {
        // Arrange
        $enemy        = $this->getResolvableEnemy();
        $dungeonRoute = $this->createDungeonRouteFor($enemy);

        try {
            $combatLogRoute = $this->createCombatLogRoute($enemy, self::FAR_DISTANCE);

            // Act
            $this->saveEnemyRecordings($dungeonRoute, $combatLogRoute);

            // Assert
            $resolutions = CombatLogRouteEnemyResolution::query()->where('dungeon_route_id', $dungeonRoute->id)->get();
            $this->assertCount(1, $resolutions);

            $resolution = $resolutions->first();
            $this->assertSame($enemy->id, $resolution->enemy_id);
            $this->assertSame($enemy->npc_id, $resolution->npc_id);
            $this->assertSame($enemy->floor_id, $resolution->floor_id);
            $this->assertSame($dungeonRoute->dungeon_id, $resolution->dungeon_id);
            $this->assertEqualsWithDelta(self::FAR_DISTANCE, $resolution->distance, 0.001);
            $this->assertEqualsWithDelta((float)$enemy->lat, $resolution->enemy_lat, 0.001);
            $this->assertEqualsWithDelta((float)$enemy->lng, $resolution->enemy_lng, 0.001);
            $this->assertNull($resolution->source);
        } finally {
            $this->cleanUp($dungeonRoute);
        }
    }

    #[Test]
    public function buildAndSave_givenMatchCloserThanThreshold_recordsNothing(): void
    {
        // Arrange
        $enemy        = $this->getResolvableEnemy();
        $dungeonRoute = $this->createDungeonRouteFor($enemy);

        try {
            $closeDistance  = (float)config('keystoneguru.enemy_resolution.record_min_distance_yd') - 1;
            $combatLogRoute = $this->createCombatLogRoute($enemy, $closeDistance);

            // Act
            $this->saveEnemyRecordings($dungeonRoute, $combatLogRoute);

            // Assert
            $this->assertSame(0, CombatLogRouteEnemyResolution::query()->where('dungeon_route_id', $dungeonRoute->id)->count());
        } finally {
            $this->cleanUp($dungeonRoute);
        }
    }

    /**
     * A boss is the only enemy of its npc in the mapping, so the matcher skips its range check entirely and a boss
     * match is unambiguous however far away it was logged. Recording it would be noise.
     */
    #[Test]
    public function buildAndSave_givenBossMatchedFromFarAway_recordsNothing(): void
    {
        // Arrange
        $enemy = Enemy::query()
            ->whereNotNull('floor_id')
            ->whereNotNull('npc_id')
            ->whereHas('npc', static fn($query) => $query->whereIn('classification_id', self::bossClassificationIds()))
            ->with(['floor', 'npc'])
            ->first();
        $this->assertNotNull($enemy, 'Expected at least one seeded boss Enemy with a floor.');

        $dungeonRoute = $this->createDungeonRouteFor($enemy);

        try {
            $combatLogRoute = $this->createCombatLogRoute($enemy, self::FAR_DISTANCE);

            // Act
            $this->saveEnemyRecordings($dungeonRoute, $combatLogRoute);

            // Assert
            $this->assertSame(0, CombatLogRouteEnemyResolution::query()->where('dungeon_route_id', $dungeonRoute->id)->count());
        } finally {
            $this->cleanUp($dungeonRoute);
        }
    }

    /**
     * A patrolling enemy stands wherever its patrol takes it, so the honest distance is the one to its closest patrol
     * vertex - not the one to the anchor point it is mapped on. Engaging right on top of a vertex must therefore
     * record nothing, even though the distance the matcher measured was far past the threshold.
     */
    #[Test]
    public function buildAndSave_givenPatrollingEnemyEngagedOnAPatrolVertex_recordsNothing(): void
    {
        // Arrange
        $enemy        = $this->getResolvableEnemy();
        $dungeonRoute = $this->createDungeonRouteFor($enemy);
        $enemyPatrol  = null;
        $polylineId   = null;

        try {
            // The patrol runs right through where the npc is engaged below, while the enemy itself stays mapped where
            // it is - so the anchor distance is far and the vertex distance is zero.
            [$enemyPatrol, $polylineId] = $this->createPatrolThrough($enemy, new LatLng((float)$enemy->lat, (float)$enemy->lng, $enemy->floor));

            $enemy->update(['enemy_patrol_id' => $enemyPatrol->id]);
            $enemy->refresh();
            $enemy->load(['floor', 'npc', 'enemyPatrol.polyline']);

            $combatLogRoute = $this->createCombatLogRoute($enemy, self::FAR_DISTANCE);

            // Act
            $this->saveEnemyRecordings($dungeonRoute, $combatLogRoute);

            // Assert - the engagement sits on a patrol vertex, so the corrected distance is 0 and nothing is recorded
            $this->assertSame(0, CombatLogRouteEnemyResolution::query()->where('dungeon_route_id', $dungeonRoute->id)->count());
        } finally {
            $enemy->update(['enemy_patrol_id' => null]);

            if ($enemyPatrol !== null) {
                EnemyPatrol::query()->whereKey($enemyPatrol->id)->delete();
            }

            if ($polylineId !== null) {
                Polyline::query()->whereKey($polylineId)->delete();
            }

            $this->cleanUp($dungeonRoute);
        }
    }

    /**
     * The matcher judges on the distance skewed by the enemy's kill priority, treating a high priority enemy as closer
     * than it is so it wins the match on purpose. The recorded distance follows that same skew, so a long line that
     * kill priority explains stays out of the recording.
     */
    #[Test]
    public function buildAndSave_givenHighKillPriorityEnemy_recordsTheWeightedDistance(): void
    {
        // Arrange
        $enemy                = $this->getResolvableEnemy();
        $originalKillPriority = $enemy->kill_priority;
        $dungeonRoute         = $this->createDungeonRouteFor($enemy);

        try {
            // Kill priority 10 halves the distance the matcher judges on
            $enemy->update(['kill_priority' => 10]);
            $enemy->refresh();
            $enemy->load(['floor', 'npc']);

            $rawDistance    = self::FAR_DISTANCE;
            $combatLogRoute = $this->createCombatLogRoute($enemy, $rawDistance);

            // Act
            $this->saveEnemyRecordings($dungeonRoute, $combatLogRoute);

            // Assert
            $resolution = CombatLogRouteEnemyResolution::query()->where('dungeon_route_id', $dungeonRoute->id)->first();
            $this->assertNotNull($resolution);
            $this->assertEqualsWithDelta($rawDistance, $resolution->distance, 0.001);
            $this->assertEqualsWithDelta($rawDistance * 0.5, $resolution->weighted_distance, 0.001);
        } finally {
            $enemy->update(['kill_priority' => $originalKillPriority]);

            $this->cleanUp($dungeonRoute);
        }
    }

    /**
     * A regeneration replaces what an earlier generation recorded for the same route rather than adding to it.
     */
    #[Test]
    public function replace_givenRouteWithExistingResolutions_replacesThemInsteadOfAddingTo(): void
    {
        // Arrange
        $enemy        = $this->getResolvableEnemy();
        $dungeonRoute = $this->createDungeonRouteFor($enemy);

        try {
            $combatLogRoute = $this->createCombatLogRoute($enemy, self::FAR_DISTANCE);
            $this->saveEnemyRecordings($dungeonRoute, $combatLogRoute);
            $this->assertSame(1, CombatLogRouteEnemyResolution::query()->where('dungeon_route_id', $dungeonRoute->id)->count());

            $builder = app(CombatLogRouteEnemyRecordingsBuilder::class);

            $secondDistance  = self::FAR_DISTANCE + 25;
            $enemyRecordings = $builder->build(
                $dungeonRoute->mappingVersion,
                $this->createCombatLogRoute($enemy, $secondDistance),
                $dungeonRoute,
                $dungeonRoute->id,
            );

            // Act
            $builder->replace($dungeonRoute, $enemyRecordings);

            // Assert
            $resolutions = CombatLogRouteEnemyResolution::query()->where('dungeon_route_id', $dungeonRoute->id)->get();
            $this->assertCount(1, $resolutions);
            $this->assertEqualsWithDelta($secondDistance, $resolutions->first()->distance, 0.001);
        } finally {
            $this->cleanUp($dungeonRoute);
        }
    }

    /**
     * A floor with unset ingame coordinates cannot locate the engagement on the map. As with the enemy failures, this
     * is diagnostic bookkeeping - it must be skipped rather than fail the whole combat log route submission.
     */
    #[Test]
    public function buildAndSave_givenFloorWithoutIngameCoordinates_skipsWithoutThrowing(): void
    {
        // Arrange
        $enemy          = $this->getResolvableEnemy();
        $floor          = $enemy->floor;
        $originalCoords = [
            'ingame_min_x' => $floor->ingame_min_x,
            'ingame_min_y' => $floor->ingame_min_y,
            'ingame_max_x' => $floor->ingame_max_x,
            'ingame_max_y' => $floor->ingame_max_y,
        ];
        $dungeonRoute = $this->createDungeonRouteFor($enemy);

        try {
            $floor->update(['ingame_min_x' => 0, 'ingame_min_y' => 0, 'ingame_max_x' => 0, 'ingame_max_y' => 0]);
            $floor->refresh();
            $enemy->load(['floor', 'npc']);

            $combatLogRoute = $this->createCombatLogRoute($enemy, self::FAR_DISTANCE);

            // Act
            $this->saveEnemyRecordings($dungeonRoute, $combatLogRoute);

            // Assert
            $this->assertSame(0, CombatLogRouteEnemyResolution::query()->where('dungeon_route_id', $dungeonRoute->id)->count());
        } finally {
            $floor->update($originalCoords);

            $this->cleanUp($dungeonRoute);
        }
    }

    /**
     * @return array<int, int>
     */
    private static function bossClassificationIds(): array
    {
        return [
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_BOSS],
            NpcClassification::ALL[NpcClassification::NPC_CLASSIFICATION_FINAL_BOSS],
        ];
    }

    private function getResolvableEnemy(): Enemy
    {
        $enemy = Enemy::query()
            ->whereNotNull('floor_id')
            ->whereNotNull('npc_id')
            ->whereNull('enemy_patrol_id')
            ->whereHas('floor', static fn($query) => $query->where('facade', 0)->where('ingame_max_x', '!=', 0))
            ->whereHas('npc', static fn($query) => $query->whereNotIn('classification_id', self::bossClassificationIds()))
            ->with(['floor', 'npc'])
            ->first();

        $this->assertNotNull($enemy, 'Expected at least one seeded non-boss Enemy on a floor with ingame coordinates.');

        return $enemy;
    }

    private function createDungeonRouteFor(Enemy $enemy): DungeonRoute
    {
        return DungeonRoute::factory()->create([
            'dungeon_id'         => $enemy->floor->dungeon_id,
            'mapping_version_id' => $enemy->mapping_version_id,
        ]);
    }

    /**
     * A combat log route holding one npc that resolved to $enemy from $distance ingame yards away. The engagement is
     * placed on the enemy's own position - only the distance handed in decides what gets recorded, except where a
     * patrol makes the real geometry matter.
     */
    private function createCombatLogRoute(Enemy $enemy, float $distance): CombatLogRouteRequestDto
    {
        /** @var CoordinatesServiceInterface $coordinatesService */
        $coordinatesService = app(CoordinatesServiceInterface::class);

        $ingameXY = $coordinatesService->calculateIngameLocationForMapLocation(
            new LatLng((float)$enemy->lat, (float)$enemy->lng, $enemy->floor),
        );

        $npc = new CombatLogRouteNpcRequestDto(
            npcId: $enemy->npc_id,
            coord: new CombatLogRouteCoordRequestDto($ingameXY->getX(), $ingameXY->getY(), $enemy->floor->ui_map_id),
        );
        $npc->setResolvedEnemy($enemy)->setResolvedEnemyDistance($distance);

        return new CombatLogRouteRequestDto(npcs: new Collection([$npc]));
    }

    /**
     * @return array{0: EnemyPatrol, 1: int} the patrol and the id of the polyline holding its vertices
     */
    private function createPatrolThrough(Enemy $enemy, LatLng $vertex): array
    {
        $enemyPatrol = EnemyPatrol::create([
            'mapping_version_id' => $enemy->mapping_version_id,
            'floor_id'           => $enemy->floor_id,
            'polyline_id'        => -1,
            'teeming'            => null,
            'faction'            => 'any',
        ]);

        $polyline = Polyline::create([
            'model_id'       => $enemyPatrol->id,
            'model_class'    => EnemyPatrol::class,
            'color'          => '#f00000',
            'color_animated' => null,
            'weight'         => 2,
            'vertices_json'  => json_encode([
                ['lat' => $vertex->getLat(), 'lng' => $vertex->getLng()],
            ]),
        ]);

        $enemyPatrol->update(['polyline_id' => $polyline->id]);
        $enemyPatrol->load('polyline');

        return [$enemyPatrol, $polyline->id];
    }

    private function saveEnemyRecordings(DungeonRoute $dungeonRoute, CombatLogRouteRequestDto $combatLogRoute): void
    {
        app(CombatLogRouteEnemyRecordingsBuilder::class)
            ->buildAndSave($dungeonRoute->mappingVersion, $combatLogRoute, $dungeonRoute);
    }

    private function cleanUp(DungeonRoute $dungeonRoute): void
    {
        CombatLogRouteEnemyResolution::query()->where('dungeon_route_id', $dungeonRoute->id)->delete();

        $dungeonRoute->delete();
    }
}
