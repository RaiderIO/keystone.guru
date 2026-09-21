<?php

namespace App\Service\DungeonRoute\Exceptions;

use Exception;

/**
 * Thrown when a route is asked to continue into a newer season it cannot continue into - its dungeon is in no
 * newer season, or the route was already continued there.
 */
class SeasonContinuationException extends Exception
{
}
