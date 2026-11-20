<?php

namespace App\Models\WalkGraph;

/**
 * Where a {@see WalkGraph} row came from.
 */
enum WalkGraphSource: string
{
    /** Loaded from the dungeon seeder files, which carry a copy of production's graph. */
    case Seeder = 'seeder';

    /** Built from this environment's own ingested combat logs. */
    case CombatLog = 'combat_log';
}
