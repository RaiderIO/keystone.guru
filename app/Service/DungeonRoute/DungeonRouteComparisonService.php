<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use Illuminate\Support\Collection;

class DungeonRouteComparisonService implements DungeonRouteComparisonServiceInterface
{
    public function findSimilarRoutes(
        DungeonRoute $route,
        int          $limit = 5,
    ): Collection {
        /**
         * SELECT
         * dr2.id,
         * dr2.public_key,
         * dr2.title,
         * dr2.popularity,
         * COUNT(*) AS common_enemies_killed
         * FROM
         * ( -- base route unique enemy set
         * SELECT DISTINCT kze.npc_id, kze.mdt_id
         * FROM kill_zones kz
         * JOIN kill_zone_enemies kze ON kze.kill_zone_id = kz.id
         * WHERE kz.dungeon_route_id = 787090
         * ) base
         * JOIN
         * (
         * SELECT id, public_key, title, popularity
         * FROM dungeon_routes
         * WHERE mapping_version_id = 610
         * AND published_state_id = 4
         * AND id <> 787090
         * AND (clone_of IS NULL OR clone_of <> 'DhJs0qr')
         * ORDER BY popularity DESC
         * LIMIT 100
         * ) dr2
         * JOIN kill_zones kz2
         * ON kz2.dungeon_route_id = dr2.id
         * JOIN kill_zone_enemies kze2
         * ON kze2.kill_zone_id = kz2.id
         * AND kze2.npc_id = base.npc_id
         * AND kze2.mdt_id = base.mdt_id
         * GROUP BY dr2.id, dr2.public_key, dr2.title
         * HAVING common_enemies_killed < (
         * SELECT COUNT(*)
         * FROM (
         * SELECT DISTINCT kze.npc_id, kze.mdt_id
         * FROM kill_zones kz
         * JOIN kill_zone_enemies kze ON kze.kill_zone_id = kz.id
         * WHERE kz.dungeon_route_id = 787090
         * AND kze.npc_id IS NOT NULL
         * AND kze.mdt_id IS NOT NULL
         * ) x
         * )
         * ORDER BY common_enemies_killed DESC;
         */

        return collect();
    }
}
