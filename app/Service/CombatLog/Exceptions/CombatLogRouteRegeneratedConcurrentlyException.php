<?php

namespace App\Service\CombatLog\Exceptions;

use Exception;

/**
 * Thrown when a regeneration loses the race for the route it was replacing: by the time the replacement was built,
 * the old route's ChallengeModeRun had already been moved (or deleted) by another regeneration of the same public key.
 */
class CombatLogRouteRegeneratedConcurrentlyException extends Exception
{
}
