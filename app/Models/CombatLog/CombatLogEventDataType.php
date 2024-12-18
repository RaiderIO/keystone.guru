<?php

namespace App\Models\CombatLog;

enum CombatLogEventDataType: string
{
    case PlayerPosition = 'player_position';
    case EnemyPosition  = 'enemy_position';
}
