<?php

namespace App\Models\LiveSession;

/**
 * The state a live session has recorded for one enemy. Obsolete enemies are not stored: they are always derived
 * from the overpulls by {@see \App\Service\LiveSession\OverpulledEnemyServiceInterface::getRouteCorrection()}.
 */
enum LiveSessionEnemyStateType: string
{
    case Killed   = 'killed';
    case InCombat = 'in_combat';
}
