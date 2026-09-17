<?php

namespace App\Service\CombatLog\Builders;

use App\Dto\Request\CombatLog\Route\CombatLogRouteNpcRequestDto;
use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Logic\Structs\IngameXY;
use App\Logic\Structs\LatLng;
use App\Models\CombatLog\CombatLogRouteEnemyFailure;
use App\Models\CombatLog\CombatLogRouteEnemyResolution;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Enemy;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\Builders\Logging\CombatLogRouteEnemyRecordingsBuilderLoggingInterface;
use App\Service\CombatLog\CombatLogRouteEnemyFailureServiceInterface;
use App\Service\CombatLog\Dtos\CombatLogRouteEnemyRecordings;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

/**
 * Works out and stores what one combat log route says about how well the Auto Route Creator matched its npcs to the
 * mapping: the npcs that matched no enemy at all (combat_log_route_enemy_failures) and the ones that matched an enemy
 * far enough away that the match is worth a second look (combat_log_route_enemy_resolutions).
 */
readonly class CombatLogRouteEnemyRecordingsBuilder
{
    /** @var int Rows per insert statement, so a combat log with many unresolved npcs stays under max_allowed_packet. */
    private const int INSERT_CHUNK_SIZE = 500;

    public function __construct(
        private CoordinatesServiceInterface                          $coordinatesService,
        private CombatLogRouteEnemyFailureServiceInterface           $combatLogRouteEnemyFailureService,
        private CombatLogRouteEnemyRecordingsBuilderLoggingInterface $log,
    ) {
    }

    /**
     * The diagnostic rows this combat log produces. Writes nothing, so a regeneration can work them out before
     * apply() replaces the original's content.
     *
     * @param DungeonRoute $dungeonRoute   the route this generation was built into
     * @param int          $dungeonRouteId the route the rows are recorded against
     */
    public function build(
        MappingVersion           $mappingVersion,
        CombatLogRouteRequestDto $combatLogRoute,
        DungeonRoute             $dungeonRoute,
        int                      $dungeonRouteId,
    ): CombatLogRouteEnemyRecordings {
        $now                  = now();
        $failureAttributes    = [];
        $resolutionAttributes = [];
        $minDistance          = (float)config('keystoneguru.enemy_resolution.record_min_distance_yd');

        /** @var Floor|null $previousFloor */
        $previousFloor = $dungeonRoute->dungeon->floors()->firstWhere('default', 1);

        // An empty set means nothing is tuned in this mapping version yet - then every failure is worth recording
        $nonZeroEnemyForcesNpcIds = array_fill_keys(
            $this->combatLogRouteEnemyFailureService->getNonZeroEnemyForcesNpcIds($mappingVersion),
            true,
        );

        foreach ($combatLogRoute->npcs as $combatLogRouteNpc) {
            $resolvedEnemy = $combatLogRouteNpc->getResolvedEnemy();
            $currentFloor  = $resolvedEnemy?->floor ?? $previousFloor; // @phpstan-ignore nullsafe.neverNull

            if ($currentFloor === null) {
                continue;
            }

            // Track the floor regardless of whether a row gets recorded below - later npcs that
            // fall back to $previousFloor must still see this npc's floor even if this one is skipped.
            $previousFloor = $currentFloor;

            if ($resolvedEnemy === null) {
                $failureAttributes[] = $this->getEnemyFailureAttributes(
                    $mappingVersion,
                    $combatLogRouteNpc,
                    $dungeonRoute,
                    $dungeonRouteId,
                    $currentFloor,
                    $nonZeroEnemyForcesNpcIds,
                    $now,
                );

                continue;
            }

            $resolutionAttributes[] = $this->getEnemyResolutionAttributes(
                $mappingVersion,
                $combatLogRouteNpc,
                $resolvedEnemy,
                $dungeonRoute,
                $dungeonRouteId,
                $currentFloor,
                $minDistance,
                $now,
            );
        }

        return new CombatLogRouteEnemyRecordings(
            array_values(array_filter($failureAttributes)),
            array_values(array_filter($resolutionAttributes)),
        );
    }

    /**
     * Works out the rows for a route that was freshly built, and stores them.
     */
    public function buildAndSave(
        MappingVersion           $mappingVersion,
        CombatLogRouteRequestDto $combatLogRoute,
        DungeonRoute             $dungeonRoute,
    ): void {
        $this->save($this->build($mappingVersion, $combatLogRoute, $dungeonRoute, $dungeonRoute->id));
    }

    public function save(CombatLogRouteEnemyRecordings $enemyRecordings): void
    {
        $this->insertEnemyFailures($enemyRecordings->failures);
        $this->insertEnemyResolutions($enemyRecordings->resolutions);
    }

    /**
     * Swaps a regenerated route's enemy failures and resolutions for the ones its latest generation computed, all or
     * nothing. They live on the combatlog connection, so they cannot share apply()'s transaction: by the time this
     * runs the route's new content is live, and a failure here is logged rather than reported as a failed
     * regeneration.
     */
    public function replace(DungeonRoute $dungeonRoute, CombatLogRouteEnemyRecordings $enemyRecordings): void
    {
        try {
            DB::connection(new CombatLogRouteEnemyFailure()->getConnectionName())->transaction(
                function () use ($dungeonRoute, $enemyRecordings): void {
                    $dungeonRoute->deleteCombatLogRouteEnemyFailures();
                    $dungeonRoute->deleteCombatLogRouteEnemyResolutions();

                    $this->save($enemyRecordings);
                },
            );
        } catch (Throwable $throwable) {
            $this->log->replaceFailed($dungeonRoute->id, $throwable->getMessage());
        }
    }

    /**
     * The combat_log_route_enemy_failures row for one npc that resolved to no enemy at all, or null when the failure
     * is not worth recording.
     *
     * @param  array<int, bool>          $nonZeroEnemyForcesNpcIds keyed by npc id
     * @return array<string, mixed>|null
     */
    private function getEnemyFailureAttributes(
        MappingVersion              $mappingVersion,
        CombatLogRouteNpcRequestDto $combatLogRouteNpc,
        DungeonRoute                $dungeonRoute,
        int                         $dungeonRouteId,
        Floor                       $currentFloor,
        array                       $nonZeroEnemyForcesNpcIds,
        Carbon                      $now,
    ): ?array {
        // An npc not worth any enemy forces in this mapping version never affects the route that gets built,
        // so failing to place it is noise rather than a mapping problem worth triaging. That covers npcs the
        // mapping does not know at all - temporary adds spawned mid-fight, which are the bulk of the volume.
        // A failure without an npc id is kept - nothing attributes it to an npc, so nothing marks it as noise.
        if ($combatLogRouteNpc->npcId !== null &&
            $nonZeroEnemyForcesNpcIds !== [] &&
            !isset($nonZeroEnemyForcesNpcIds[$combatLogRouteNpc->npcId])) {
            $this->log->buildSkippingNpcWithoutEnemyForces($dungeonRouteId, $combatLogRouteNpc->npcId);

            return null;
        }

        // This table is diagnostic bookkeeping only (unresolved-npc triage) - a floor with unset ingame coordinates
        // (a mapping data gap) must not fail the whole combat log route submission just because it can't be
        // recorded here.
        $latLng = $this->getMapLocation($combatLogRouteNpc, $dungeonRouteId, $currentFloor);

        if ($latLng === null) {
            return null;
        }

        return array_merge([
            'dungeon_route_id'   => $dungeonRouteId,
            'dungeon_id'         => $dungeonRoute->dungeon_id,
            'floor_id'           => $currentFloor->id,
            'mapping_version_id' => $mappingVersion->id,
            'npc_id'             => $combatLogRouteNpc->npcId,
            'created_at'         => $now,
            'updated_at'         => $now,
        ], $latLng->toArray());
    }

    /**
     * The combat_log_route_enemy_resolutions row for one npc that did resolve to a mapped enemy, or null when the
     * match is not worth recording.
     *
     * @return array<string, mixed>|null
     */
    private function getEnemyResolutionAttributes(
        MappingVersion              $mappingVersion,
        CombatLogRouteNpcRequestDto $combatLogRouteNpc,
        Enemy                       $resolvedEnemy,
        DungeonRoute                $dungeonRoute,
        int                         $dungeonRouteId,
        Floor                       $currentFloor,
        float                       $minDistance,
        Carbon                      $now,
    ): ?array {
        $distance = $combatLogRouteNpc->getResolvedEnemyDistance();

        // Only the combat log route builder measures this - a route built from result events records nothing
        if ($distance === null) {
            return null;
        }

        // There is only one boss npc in the mapping, so a boss match is unambiguous however far away it was logged -
        // the matcher skips its range check for exactly that reason. Recording the distance would only add noise.
        if ($resolvedEnemy->npc?->isBoss() ?? false) {
            return null;
        }

        // The matcher judges on the kill priority skewed distance rather than the real one, deliberately treating a
        // high priority enemy as closer than it is. Recording that same skewed distance keeps a long line that was
        // intended out of the red.
        $weightFactor     = 1 + ($resolvedEnemy->kill_priority * DungeonRouteBuilder::ENEMY_KILL_PRIORITY_WEIGHT_RATIO);
        $weightedDistance = $distance * $weightFactor;

        if ($weightedDistance < $minDistance) {
            return null;
        }

        // A patrolling enemy is wherever its patrol is, so the honest distance is the one to its closest vertex. The
        // matcher only considers those vertices in its last resort strategy, which leaves an enemy matched through an
        // engaged pack measured against a mapped anchor point it may never stand on.
        $patrolDistance = $this->getClosestPatrolVertexDistance($resolvedEnemy, $combatLogRouteNpc);
        if ($patrolDistance !== null && $patrolDistance < $distance) {
            $distance         = $patrolDistance;
            $weightedDistance = $distance * $weightFactor;

            if ($weightedDistance < $minDistance) {
                return null;
            }
        }

        // As with the failures above, a floor with unset ingame coordinates must not fail the whole submission
        $latLng = $this->getMapLocation($combatLogRouteNpc, $dungeonRouteId, $currentFloor);

        if ($latLng === null) {
            return null;
        }

        return [
            'dungeon_route_id'   => $dungeonRouteId,
            'dungeon_id'         => $dungeonRoute->dungeon_id,
            'floor_id'           => $currentFloor->id,
            'mapping_version_id' => $mappingVersion->id,
            'npc_id'             => $combatLogRouteNpc->npcId,
            'enemy_id'           => $resolvedEnemy->id,
            'lat'                => $latLng->getLat(),
            'lng'                => $latLng->getLng(),
            'enemy_lat'          => $resolvedEnemy->lat,
            'enemy_lng'          => $resolvedEnemy->lng,
            'distance'           => $distance,
            'weighted_distance'  => $weightedDistance,
            'created_at'         => $now,
            'updated_at'         => $now,
        ];
    }

    /**
     * Where on the map the npc was engaged, or null when the floor has no ingame coordinates to convert with.
     */
    private function getMapLocation(CombatLogRouteNpcRequestDto $combatLogRouteNpc, int $dungeonRouteId, Floor $currentFloor): ?LatLng
    {
        try {
            return $this->coordinatesService->calculateMapLocationForIngameLocation(
                new IngameXY(
                    $combatLogRouteNpc->coord->x,
                    $combatLogRouteNpc->coord->y,
                    $currentFloor,
                ),
            );
        } catch (InvalidArgumentException) {
            $this->log->buildUnableToCalculateMapLocation($dungeonRouteId, $combatLogRouteNpc->npcId, $currentFloor->id);

            return null;
        }
    }

    /**
     * The ingame yards between where the npc was engaged and the closest vertex of the enemy's patrol, or null when
     * the enemy does not patrol or its patrol cannot be converted to ingame coordinates.
     */
    private function getClosestPatrolVertexDistance(Enemy $enemy, CombatLogRouteNpcRequestDto $combatLogRouteNpc): ?float
    {
        if ($enemy->enemyPatrol?->polyline === null) {
            return null;
        }

        $closestDistance = null;

        try {
            foreach ($enemy->enemyPatrol->polyline->getDecodedLatLngs($enemy->floor) as $latLng) {
                $vertexIngameXY = $this->coordinatesService->calculateIngameLocationForMapLocation($latLng);

                $vertexDistance = $this->coordinatesService->distanceBetweenPoints(
                    $vertexIngameXY->getX(),
                    $combatLogRouteNpc->coord->x,
                    $vertexIngameXY->getY(),
                    $combatLogRouteNpc->coord->y,
                );

                if ($closestDistance === null || $vertexDistance < $closestDistance) {
                    $closestDistance = $vertexDistance;
                }
            }
        } catch (InvalidArgumentException) {
            return null;
        }

        return $closestDistance;
    }

    /**
     * @param array<int, array<string, mixed>> $failureAttributes
     */
    private function insertEnemyFailures(array $failureAttributes): void
    {
        foreach (array_chunk($failureAttributes, self::INSERT_CHUNK_SIZE) as $chunk) {
            CombatLogRouteEnemyFailure::insert($chunk);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $resolutionAttributes
     */
    private function insertEnemyResolutions(array $resolutionAttributes): void
    {
        foreach (array_chunk($resolutionAttributes, self::INSERT_CHUNK_SIZE) as $chunk) {
            CombatLogRouteEnemyResolution::insert($chunk);
        }
    }
}
