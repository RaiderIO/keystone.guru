<?php

namespace App\Http\Controllers\Traits;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteLimitType;

/**
 * Caps on how many objects of a given type a single dungeon route may hold.
 *
 * These used to be DungeonRoutePolicy::addKillZone/addBrushline/addPath/addArrow/addMapIcon, which
 * made a quota breach come back as a 403. Hitting a configured cap is not "you are not allowed" -
 * it is a request the server understood and refuses to process, i.e. a 422. That is also what the
 * rest of the codebase already returns for quota and Patreon-benefit limits.
 */
trait EnforcesDungeonRouteLimits
{
    /**
     * Maps each limit type onto the relation that counts against it and the message shown when
     * it is reached.
     *
     * @var array<string, array{relation: string, message: string}>
     */
    private const LIMITS = [
        DungeonRouteLimitType::KillZones->value  => ['relation' => 'killZones', 'message' => 'add_kill_zone_limit_reached'],
        DungeonRouteLimitType::Brushlines->value => ['relation' => 'brushlines', 'message' => 'add_brushline_limit_reached'],
        DungeonRouteLimitType::Paths->value      => ['relation' => 'paths', 'message' => 'add_path_limit_reached'],
        DungeonRouteLimitType::Arrows->value     => ['relation' => 'arrows', 'message' => 'add_arrow_limit_reached'],
        DungeonRouteLimitType::MapIcons->value   => ['relation' => 'mapicons', 'message' => 'add_map_icon_limit_reached'],
    ];

    /**
     * Aborts with a 422 when the route already holds the configured maximum of the given type.
     */
    protected function abortIfDungeonRouteLimitReached(DungeonRoute $dungeonRoute, DungeonRouteLimitType $type): void
    {
        ['relation' => $relation, 'message' => $message] = self::LIMITS[$type->value];

        $limit = (int)config(sprintf('keystoneguru.dungeon_route_limits.%s', $type->value));

        if ($dungeonRoute->{$relation}()->count() >= $limit) {
            abort(422, __(sprintf('policy.%s', $message), ['limit' => $limit]));
        }
    }
}
