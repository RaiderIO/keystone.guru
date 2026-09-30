<?php

namespace App\Service\DungeonRoute\Exceptions;

/**
 * Thrown when a new draft is requested for a route that already has one, and discarding that draft was not
 * confirmed.
 */
class PendingUpgradeDraftException extends UpgradeDraftException
{
}
