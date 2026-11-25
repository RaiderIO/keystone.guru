<?php

namespace App\Repositories\Database\Traits;

use App\Models\DungeonRoute\DungeonRoute;

/**
 * For repositories of a dungeon route's coupling rows: one row per id, pointing at the route.
 */
trait ReplacesDungeonRouteRows
{
    /**
     * Deletes the route's rows and inserts one row per id in their place.
     *
     * @param array<int, int|string> $ids
     */
    private function replaceDungeonRouteRows(DungeonRoute $dungeonRoute, string $foreignKey, array $ids): void
    {
        $this->class::query()->where('dungeon_route_id', $dungeonRoute->id)->delete();

        if ($ids !== []) {
            $this->class::query()->insert(array_map(static fn(int $id) => [
                'dungeon_route_id' => $dungeonRoute->id,
                $foreignKey        => $id,
            ], array_map('intval', $ids)));
        }
    }
}
