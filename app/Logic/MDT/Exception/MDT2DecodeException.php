<?php

namespace App\Logic\MDT\Exception;

use Exception;

/**
 * Thrown when a `!~MDT2~`-prefixed MDT export string cannot be decoded (invalid Base64, deflate
 * stream or CBOR payload). Messages are internal/telemetry only - user-facing errors reuse the
 * existing translated decode-failure keys.
 */
class MDT2DecodeException extends Exception
{
}
