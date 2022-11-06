<?php


namespace App\Service\LiveSession;

use App\Models\KillZone;
use App\Models\LiveSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OverpulledEnemyService implements OverpulledEnemyServiceInterface
{
    /**
     * @inheritDoc
     */
    function getRouteCorrection(LiveSession $liveSession): DungeonRouteCorrection
    {
        $dungeonRouteCorrection = new DungeonRouteCorrection($liveSession);

        // Select a list of ordered kill zones based on overpulled enemies for this live session
        // While illogical, someone can say they overpulled enemy abc on pull 24, but then say they also overpulled
        // enemy xyz on pull 23. The orders in the overpulled_enemies table cannot be trusted since it'd return (24, 23)
        // which will then throw a spanner in the works when trying to determine obsolete enemies
        $overpulledEnemyForces = $this->getOverpulledEnemyForces($liveSession);

        if ($overpulledEnemyForces->isNotEmpty()) {
            $tooMuchEnemyForces = $liveSession->dungeonroute->getEnemyForcesTooMuch();

            // Start with the first mistake that was made and work on trying to reduce the value of this until it is 0 or lower
            $enemyForcesLeftToCorrect = $tooMuchEnemyForces;

            // Get the first overpulled enemy forces we should take into account. Contains a kill zone and the enemies
            // that were killed during/after that kill zone, but shouldn't have
            foreach ($overpulledEnemyForces as $overpulledEnemyForce) {
                // We should now also take into account the new enemy forces that were overpulled
                $enemyForcesLeftToCorrect += $overpulledEnemyForce['enemy_forces'];

                // All the killzones that we can potentially take enemies from
                /** @var Collection|KillZone[] $availableKillZones */
                $availableKillZones = KillZone::where('dungeon_route_id', $liveSession->dungeon_route_id)
                    ->where('index', '>', $overpulledEnemyForce['kill_zone']->index)
                    ->get();

                // Loop over all available kill zones from which we can still potentially subtract enemy forces
                foreach ($availableKillZones as $availableKillZone) {
                    $skippableEnemyForces = $availableKillZone->getSkippableEnemyForces($liveSession->dungeonroute->teeming);

                    // Contains a list of enemies, grouped by pack, with the -1 pack being enemies that are not assigned to a pack being LAST
                    $groupedBy = $skippableEnemyForces->groupBy('enemy_pack_id')->sortDesc();

                    foreach ($groupedBy as $enemyPackId => $enemies) {
                        /** @var Collection $enemies */
                        $enemies = $enemies->sortByDesc(function ($row) {
                            return $row->enemy_forces;
                        });

                        if ($enemyPackId === -1) {
                            foreach ($enemies as $enemy) {
                                // Can skip each enemy individually here
                                if ($enemyForcesLeftToCorrect >= $enemy->enemy_forces) {
                                    $enemyForcesLeftToCorrect -= $enemy->enemy_forces;
                                    $dungeonRouteCorrection->addObsoleteEnemy($enemy->enemy_id);
                                } else {
                                    break;
                                }
                            }

                            // We don't need to consider the whole '-1' pack as a whole pack as they're individual enemies
                            continue;
                        }

                        // We need to check if we can skip all the enemies in the upcoming pack
                        $totalEnemyForcesInPack = $enemies->sum(function ($row) {
                            return $row->enemy_forces;
                        });

                        // If we can safely skip this entire pack
                        if ($enemyForcesLeftToCorrect >= $totalEnemyForcesInPack) {
                            $enemyForcesLeftToCorrect -= $totalEnemyForcesInPack;

                            // Let the user know that we no longer need these enemies
                            $dungeonRouteCorrection->addObsoleteEnemies($enemies->pluck('enemy_id'));
                        }
                    }
                }
            }

            // Correct the new enemy forces for the route - subtract the $tooMuchEnemyForces since we already corrected for them
            $dungeonRouteCorrection->setEnemyForces(($liveSession->dungeonroute->enemy_forces - $tooMuchEnemyForces) + $enemyForcesLeftToCorrect);
        }

        return $dungeonRouteCorrection;
    }

    /**
     * @param LiveSession $liveSession
     * @return Collection
     */
    private function getOverpulledEnemyForces(LiveSession $liveSession): Collection
    {
        $queryResult = DB::select('
                select `kill_zones`.*,
                       CAST(IFNULL(
                               IF(dungeon_routes.teeming = 1,
                                  SUM(
                                          IF(
                                                  enemies.enemy_forces_override_teeming IS NOT NULL,
                                                  enemies.enemy_forces_override_teeming,
                                                  IF(npcs.enemy_forces_teeming >= 0, npcs.enemy_forces_teeming, npcs.enemy_forces)
                                              )
                                      ),
                                  SUM(
                                          IF(
                                                  enemies.enemy_forces_override IS NOT NULL,
                                                  enemies.enemy_forces_override,
                                                  npcs.enemy_forces
                                              )
                                      )
                                   ), 0
                           ) AS SIGNED) as enemy_forces
                from `live_sessions`
                         left join `dungeon_routes` on `dungeon_routes`.`id` = `live_sessions`.`id`
                         left join `overpulled_enemies` on `overpulled_enemies`.`live_session_id` = `live_sessions`.`id`
                         left join `kill_zones` on `kill_zones`.`id` = `overpulled_enemies`.`kill_zone_id`
                         left join `enemies` on `enemies`.`id` = `overpulled_enemies`.`enemy_id`
                         left join `npcs` on `npcs`.`id` = `enemies`.`npc_id`
                         left join `dungeons` on `dungeons`.`id` = `dungeon_routes`.`dungeon_id`
                where `live_sessions`.id = :id
                group by `live_sessions`.id, `kill_zones`.id, `kill_zones`.`index`
                order by `kill_zones`.`index`
            ', ['id' => $liveSession->id]);

        $result = collect();

        foreach ($queryResult as $killZone) {
            // Would mean no result
            if ($killZone->id !== null) {
                $result->push([
                    'kill_zone'    => new KillZone((array)$killZone),
                    'enemy_forces' => $killZone->enemy_forces,
                ]);
            }
        }

        return $result;
    }
}
