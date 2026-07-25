<?php

namespace App\Http\Controllers\Traits;

use App\Models\DungeonRoute\DungeonRoute;

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
    public const LIMIT_KILL_ZONES = 'kill_zones';

    public const LIMIT_BRUSHLINES = 'brushlines';

    public const LIMIT_PATHS = 'paths';

    public const LIMIT_ARROWS = 'arrows';

    public const LIMIT_MAP_ICONS = 'map_icons';

    /**
     * Maps each configured limit onto the relation that counts against it and the message shown
     * when it is reached.
     *
     * @var array<string, array{relation: string, message: string}>
     */
    private const LIMITS = [
        self::LIMIT_KILL_ZONES => ['relation' => 'killZones', 'message' => 'add_kill_zone_limit_reached'],
        self::LIMIT_BRUSHLINES => ['relation' => 'brushlines', 'message' => 'add_brushline_limit_reached'],
        self::LIMIT_PATHS      => ['relation' => 'paths', 'message' => 'add_path_limit_reached'],
        self::LIMIT_ARROWS     => ['relation' => 'arrows', 'message' => 'add_arrow_limit_reached'],
        self::LIMIT_MAP_ICONS  => ['relation' => 'mapicons', 'message' => 'add_map_icon_limit_reached'],
    ];

    /**
     * Aborts with a 422 when the route already holds the configured maximum of the given type.
     *
     * @param string $type One of the LIMIT_* constants
     */
    protected function abortIfDungeonRouteLimitReached(DungeonRoute $dungeonRoute, string $type): void
    {
        ['relation' => $relation, 'message' => $message] = self::LIMITS[$type];

        $limit = (int)config(sprintf('keystoneguru.dungeon_route_limits.%s', $type));

        if ($dungeonRoute->{$relation}()->count() >= $limit) {
            abort(422, __(sprintf('policy.%s', $message), ['limit' => $limit]));
        }
    }
}
