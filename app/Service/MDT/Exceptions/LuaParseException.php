<?php

namespace App\Service\MDT\Exceptions;

use Exception;

/**
 * Thrown by {@see \App\Service\MDT\Lua\LuaTableParser} when an existing MDT dungeon file cannot be
 * understood. The parser deliberately fails loudly instead of guessing: its output decides which
 * MDT owned content is carried over into a freshly exported file, so a silently mis-parsed file
 * would quietly delete data from MDT's repository.
 */
class LuaParseException extends Exception
{
}
