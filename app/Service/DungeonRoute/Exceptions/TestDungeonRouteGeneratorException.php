<?php

namespace App\Service\DungeonRoute\Exceptions;

use Exception;

/**
 * Thrown when test routes are requested in an environment that does not allow them, for a dungeon without
 * usable mapping, or in a quantity outside the allowed range.
 */
class TestDungeonRouteGeneratorException extends Exception
{
}
