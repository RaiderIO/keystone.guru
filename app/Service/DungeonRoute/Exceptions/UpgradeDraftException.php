<?php

namespace App\Service\DungeonRoute\Exceptions;

use Exception;

/**
 * Thrown when the draft-and-apply mapping version upgrade flow is asked to do something it cannot -
 * a draft of a draft, a draft of a sandbox route, or applying/discarding a route that is not a draft.
 */
class UpgradeDraftException extends Exception
{
}
