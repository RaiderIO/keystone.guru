<?php

namespace App\Service\DungeonRoute\Exceptions;

/**
 * Thrown by apply() when the draft it was handed no longer exists, or no longer belongs to the original
 * it claimed to upgrade - another apply, discard or take-over won the race for it.
 *
 * Split out of UpgradeDraftException so a caller can tell "somebody else got here first" (retryable, and
 * for the Auto Route Creator the signal that this regeneration lost) apart from the other, non-retryable
 * refusals: not a draft at all, or an original that has since been deleted outright.
 */
class UpgradeDraftGoneException extends UpgradeDraftException
{
}
