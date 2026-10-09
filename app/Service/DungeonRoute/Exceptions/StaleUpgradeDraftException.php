<?php

namespace App\Service\DungeonRoute\Exceptions;

/**
 * Thrown when discarding a route's draft was confirmed for a draft other than the route's current one, because
 * another import or a retried request replaced it after the confirmation was given.
 */
class StaleUpgradeDraftException extends PendingUpgradeDraftException
{
}
