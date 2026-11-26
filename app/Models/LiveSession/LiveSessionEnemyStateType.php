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

    /**
     * A viewer took this enemy's overpull back, so overpull detection must not put it back when the kill that made
     * it an overpull is replayed. Never part of the live state itself.
     */
    case OverpullDismissed = 'dismissed';
}
