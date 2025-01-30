<?php

namespace App\Models\CombatLog;

enum CombatLogEventEventType: string
{
    case NpcDeath    = 'npc_death';
    case PlayerDeath = 'player_death';
    case PlayerSpell = 'player_spell';
}
