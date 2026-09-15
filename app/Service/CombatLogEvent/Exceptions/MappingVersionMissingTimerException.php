<?php

namespace App\Service\CombatLogEvent\Exceptions;

use InvalidArgumentException;

/**
 * Thrown by {@see \App\Service\CombatLogEvent\Dtos\CombatLogEventFilter::fromHeatmapDataFilter()}
 * when a timer-fraction filter is requested for a dungeon whose current mapping version has no
 * timer set. Kept as its own type rather than reusing the generic {@see InvalidArgumentException}
 * so a catch for this specific, user-reachable case doesn't also swallow unrelated argument errors
 * (e.g. missing floor/coordinate data) raised elsewhere in the same request.
 */
class MappingVersionMissingTimerException extends InvalidArgumentException
{
}
