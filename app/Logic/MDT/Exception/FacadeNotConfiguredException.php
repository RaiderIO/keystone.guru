<?php

namespace App\Logic\MDT\Exception;

use RuntimeException;

/**
 * Thrown by MDTDungeon::getClonesAsEnemies() when a mapping version has facade_enabled=true but its
 * facade is not actually usable - either no facade floor was found among the given floors, or the
 * facade floor has no floor unions to redistribute enemy coordinates back through. Both mean the
 * mapping version's facade setup is incomplete; this must be fixed on the mapping version (not
 * worked around here) before the import can run again.
 *
 * @author Wouter
 *
 * @since 29/07/2026
 */
class FacadeNotConfiguredException extends RuntimeException
{
}
