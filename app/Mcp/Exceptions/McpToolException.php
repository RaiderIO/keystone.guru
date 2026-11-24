<?php

namespace App\Mcp\Exceptions;

use Exception;

/**
 * A tool call the server understood but refuses; its message is returned to the calling AI as the tool's error.
 */
class McpToolException extends Exception
{
}
