<?php

namespace App\Models\DungeonRoute;

/**
 * The types of objects that are capped per dungeon route by EnforcesDungeonRouteLimits.
 */
enum DungeonRouteLimitType: string
{
    case KillZones  = 'kill_zones';
    case Brushlines = 'brushlines';
    case Paths      = 'paths';
    case Arrows     = 'arrows';
    case MapIcons   = 'map_icons';
}
