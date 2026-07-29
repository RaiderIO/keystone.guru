<?php

namespace App\Logic\MDT\Exception;

use Exception;

/**
 * Thrown when a legacy (pre-MDT 6.2) MDT export string cannot be decoded - `cli_weakauras_parser`
 * failed or produced output that was not valid JSON. Does not cover a missing binary - see
 * CliWeakaurasParserNotFoundException for that.
 */
class LegacyMDTDecodeException extends Exception
{
}
