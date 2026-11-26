<?php

namespace App\Service\WalkGraph;

use App\Logic\Structs\IngameXY;
use App\Models\DungeonStart;
use App\Models\Enemy;
use App\Models\EnemyPack;
use App\Models\Mapping\MappingVersion;
use Illuminate\Support\Collection;

/**
 * Which packs can be walked to without pulling anything else on the way, in in-game yards. Every standing enemy blocks
 * the walk graph within the aggro radius around it and around every point of its patrol, so a pack is only reachable
 * when the walk graph gets into its own aggro radius without entering anyone else's. A walk around blocking enemies
 * adds the detour to the distance and carries a detour ratio; one more than the maximum ratio longer than the direct
 * walk does not count.
 *
 * Floors without a usable walk graph, and packs the walk graph does not connect at all, keep the plain distances of
 * {@see PackDistanceServiceInterface}: there is no way to tell what stands in the way.
 */
interface PackReachabilityServiceInterface
{
    /**
     * The nearest reachable packs on the same floor of each pack, nearest first, with nothing killed yet but the pack
     * walked from. Only enemies that always appear block: seasonal, teeming-only and difficulty-only enemies do not.
     * Cached per floor and walk graph build.
     *
     * @param  Collection<int, EnemyPack>                                                                               $enemyPacks with their enemies and floor loaded
     * @param  Collection<int, Enemy>                                                                                   $enemies    every enemy of the mapping version that may stand in the way, with floor loaded
     * @return array<int, list<array{enemy_pack_id: int, distance: float, unverified: bool, detour_ratio: float|null}>> enemy pack id => neighbours
     */
    public function getNeighbours(MappingVersion $mappingVersion, Collection $enemyPacks, Collection $enemies, int $neighbourCount): array;

    /**
     * The nearest packs each dungeon start can open with, with nothing killed yet. Blocking enemies as in
     * {@see self::getNeighbours()}, cached the same way.
     *
     * @param  Collection<int, DungeonStart>                                                                            $dungeonStarts with their floor loaded
     * @param  Collection<int, EnemyPack>                                                                               $enemyPacks    with their enemies and floor loaded
     * @param  Collection<int, Enemy>                                                                                   $enemies       every enemy of the mapping version that may stand in the way, with floor loaded
     * @return array<int, list<array{enemy_pack_id: int, distance: float, unverified: bool, detour_ratio: float|null}>> dungeon start id => packs
     */
    public function getOpeningPacks(Collection $dungeonStarts, Collection $enemyPacks, Collection $enemies, int $packCount): array;

    /**
     * The nearest reachable packs on the point's floor, given exactly which enemies still stand - a route's state
     * after some of its pulls. Not cached.
     *
     * @param  Collection<int, EnemyPack>                                                                   $enemyPacks      with only their standing enemies and their floor loaded; packs without any are left out
     * @param  Collection<int, Enemy>                                                                       $standingEnemies with floor loaded
     * @return list<array{enemy_pack_id: int, distance: float, unverified: bool, detour_ratio: float|null}>
     */
    public function getReachablePacksFrom(IngameXY $from, Collection $enemyPacks, Collection $standingEnemies, int $packCount): array;
}
