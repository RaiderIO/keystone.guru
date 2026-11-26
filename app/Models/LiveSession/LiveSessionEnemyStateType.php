<?php

namespace App\Models\LiveSession;

/**
 * The state a live session has recorded for one enemy.
 *
 * Obsolete rows are only the set a buffer pass last broadcast in a RouteCorrectionEvent, kept so the next pass can
 * tell whether the set changed. The obsolete set itself is always derived from the overpulls by
 * {@see \App\Service\LiveSession\OverpulledEnemyServiceInterface::getRouteCorrection()}.
 */
enum LiveSessionEnemyStateType: string
{
    case Killed   = 'killed';
    case InCombat = 'in_combat';
    case Obsolete = 'obsolete';
}
