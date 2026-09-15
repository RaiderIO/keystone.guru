<?php

namespace App\Logic\CombatLog\CombatEvents\Prefixes;

/**
 * A periodic (over time) tick of a spell - `SPELL_PERIODIC_DAMAGE`, `SPELL_PERIODIC_HEAL`, ...
 *
 * Extends Spell rather than Range so that every consumer gating on the Spell prefix keeps consuming periodic
 * events exactly as it did while this class was unreachable (#4071).
 */
class SpellPeriodic extends Spell
{
}
