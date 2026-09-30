<?php

namespace App\Models;

/**
 * The group a dungeon is shown under in the dungeon selector. Cases are declared in display order.
 */
enum DungeonSelectorGroup: string
{
    case WORLD   = 'world';
    case DUNGEON = 'dungeon';
    case RAID    = 'raid';

    public function sortOrder(): int
    {
        return match ($this) {
            self::WORLD   => 0,
            self::DUNGEON => 1,
            self::RAID    => 2,
        };
    }
}
