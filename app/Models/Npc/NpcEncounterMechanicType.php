<?php

namespace App\Models\Npc;

/**
 * What a {@see NpcEncounterMechanic} row says about a boss encounter.
 */
enum NpcEncounterMechanicType: string
{
    /** A boss of the encounter. */
    case Boss = 'boss';

    /** Units that join the fight in waves after the pull. */
    case Adds = 'adds';

    /** A boss taking no damage while it carries an aura. */
    case Invulnerable = 'invulnerable';

    /** Units sharing one health pool. */
    case SharedHealth = 'shared_health';

    /** The whole party being forced to move after a cast. */
    case Movement = 'movement';

    /** An NPC fighting on the party's side. */
    case Friendly = 'friendly';

    /** An NPC whose health scales with the key level but not with Fortified or Tyrannical. */
    case AffixExempt = 'affix_exempt';
}
