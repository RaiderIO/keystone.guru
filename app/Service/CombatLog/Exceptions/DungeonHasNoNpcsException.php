<?php

namespace App\Service\CombatLog\Exceptions;

use Exception;

/**
 * Thrown when a mapping version is created from a combat log for a dungeon that has no NPCs attached to
 * it yet. Every enemy in the log is matched against the dungeon's NPCs, so without them the import
 * silently yields zero enemies - `combatlog:extractdata` is what creates the NPCs first (#4354).
 */
class DungeonHasNoNpcsException extends Exception
{
}
