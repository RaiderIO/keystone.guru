<?php

namespace App\Models\CombatLog;

enum CombatLogEventEventType: string
{
    case EnemyKilled = 'enemy_killed';
    case PlayerDeath = 'player_death';
    case SpellCast   = 'spell_cast';
}
