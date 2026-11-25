<?php

namespace App\Models\Spell;

/**
 * Where a spell's `cooldown_ms`, `charges` and `charge_cooldown_ms` came from.
 */
enum SpellCooldownSource: string
{
    /** The game client's own DB2 tables, read through wago.tools. */
    case Db2 = 'db2';

    /** Wowhead's tooltip, for a spell the game build no longer knows. */
    case Wowhead = 'wowhead';
}
