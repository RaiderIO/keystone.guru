<?php

namespace App\Service\RaiderIO\Enums;

use App\Models\Faction;

/**
 * Raider.IO's own faction numbering, as it appears on a search-advanced run and in that endpoint's
 * `faction` filter. It is not the Faction model's id, and it has no case for a cross faction group -
 * such a run carries no faction at all rather than a third value.
 */
enum RaiderIOFaction: int
{
    case Alliance = 0;

    case Horde = 1;

    /**
     * Null for a faction that has no Raider.IO counterpart, i.e. Faction::FACTION_UNSPECIFIED.
     */
    public static function fromFaction(?Faction $faction): ?self
    {
        return match ($faction?->key) {
            Faction::FACTION_ALLIANCE => self::Alliance,
            Faction::FACTION_HORDE    => self::Horde,
            default                   => null,
        };
    }
}
