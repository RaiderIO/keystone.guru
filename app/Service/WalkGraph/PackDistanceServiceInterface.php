<?php

namespace App\Service\WalkGraph;

use App\Logic\Structs\IngameXY;
use App\Models\EnemyPack;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

/**
 * Reads how far packs are from each other, in in-game yards: the walking distance from the walk graph where one is
 * stored, otherwise the straight line between the packs, flagged unverified.
 */
interface PackDistanceServiceInterface
{
    /**
     * The in-game centroid of each pack's enemies.
     *
     * @param  Collection<int, EnemyPack> $enemyPacks with their enemies and floor loaded
     * @return array<int, IngameXY>       enemy pack id => centroid
     */
    public function getPackCentroids(Collection $enemyPacks): array;

    /**
     * The nearest packs of each pack, nearest first. A pack on another floor is walked to through the floor switch
     * markers, and lists the markers taken on the way in order; it is unverified when any part of the walk is.
     *
     * @param  Collection<int, EnemyPack>                                                                                         $enemyPacks with their enemies and floor loaded
     * @return array<int, list<array{enemy_pack_id: int, distance: float, unverified: bool, floor_switch_marker_ids: list<int>}>> enemy pack id => neighbours
     */
    public function getNeighbours(MappingVersion $mappingVersion, Collection $enemyPacks, int $neighbourCount): array;
}
