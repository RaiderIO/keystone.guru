<?php

namespace App\Logic\MDT\Exception;

use Exception;

/**
 * Thrown when the legacy (pre-MDT 6.2) MDT export-string format cannot be encoded - the contents
 * could not be JSON-serialized, or `cli_weakauras_parser` produced no usable output.
 */
class LegacyMDTEncodeException extends Exception
{
}
