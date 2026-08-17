<?php

namespace App\Logic\CombatLog\CombatEvents\Prefixes;

/**
 * A spell hitting a building/structure - `SPELL_BUILDING_DAMAGE`, `SPELL_BUILDING_HEAL`.
 *
 * Extends Spell rather than Range so that every consumer gating on the Spell prefix keeps consuming building
 * events exactly as it did while this class was unreachable (#4071).
 */
class SpellBuilding extends Spell
{
}
