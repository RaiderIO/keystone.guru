<?php

namespace App\Logic\MDT\Exception;

use Exception;

/**
 * Thrown when a PHP value cannot be encoded into the `!~MDT2~` MDT export-string format
 * (unsupported value type, or an integer beyond CBOR's basic 32-bit encoding range).
 */
class MDT2EncodeException extends Exception
{
}
