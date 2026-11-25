<?php

namespace App\Models\Spell;

/**
 * Where a spell's `cooldown_ms`, `charges` and `charge_cooldown_ms` came from.
 */
enum SpellCooldownSource: string
{
    /** The game client's own DB2 tables, read through wago.tools. */
    case Db2 = 'db2';

    /** Wowhead's tooltip, for a player spell missing from the game build's DB2 tables. */
    case Wowhead = 'wowhead';
}
