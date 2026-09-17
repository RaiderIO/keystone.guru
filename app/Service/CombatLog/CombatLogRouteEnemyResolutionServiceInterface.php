<?php

namespace App\Service\CombatLog;

use App\Models\Dungeon;
use App\Models\Mapping\MappingVersion;
use App\Service\CombatLog\Dtos\CombatLogRouteEnemyResolutionHeatmapResult;
use App\Service\CombatLog\Enums\EnemyResolutionHeatmapMetric;
use Illuminate\Support\Collection;

interface CombatLogRouteEnemyResolutionServiceInterface
{
    /**
     * @param int[]|null $npcIds
     */
    public function getResolutionHeatmapData(
        Dungeon                      $dungeon,
        MappingVersion               $mappingVersion,
        ?array                       $npcIds,
        EnemyResolutionHeatmapMetric $metric,
        ?float                       $minDistance = null,
    ): CombatLogRouteEnemyResolutionHeatmapResult;

    /**
     * The worst recorded matches, as lines from where the engagement happened to the enemy it resolved to, ready to
     * draw. Coordinates come back in the display coordinates of whichever map style the current user is on, and
     * floor_id is the floor the line belongs on in that style.
     *
     * Both distances are the ones recorded at the time of the match. The weighted one is what the matcher judged on,
     * so the pair says whether kill priority moved this match - reading that off the enemy as it is mapped today
     * would describe a different match than the one recorded.
     *
     * @param int[]|null $npcIds
     * @return array<int, array{
     *     floor_id: int,
     *     lat: float,
     *     lng: float,
     *     enemy_lat: float,
     *     enemy_lng: float,
     *     distance: float,
     *     weighted_distance: float,
     *     npc_id: int|null,
     *     npc_name: string|null,
     *     enemy_id: int,
     *     source: string|null,
     *     dungeon_route_id: int|null,
     *     dungeon_route_public_key: string|null,
     *     dungeon_route_url: string|null,
     * }>
     */
    public function getResolutionLines(
        Dungeon        $dungeon,
        MappingVersion $mappingVersion,
        ?array         $npcIds,
        ?float         $minDistance,
        int            $limit,
    ): array;

    /**
     * @return Collection<int, int> npc_id => recorded resolution count, most first
     */
    public function getResolutionCountsPerNpc(Dungeon $dungeon, MappingVersion $mappingVersion): Collection;

    /**
     * @return Collection<int, int> mapping_version_id => recorded resolution count, for every mapping version of the dungeon
     */
    public function getResolutionCountsPerMappingVersion(Dungeon $dungeon): Collection;
}
