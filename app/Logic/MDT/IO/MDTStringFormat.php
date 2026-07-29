<?php

namespace App\Logic\MDT\IO;

use Throwable;

/**
 * The MDT export-string formats this application understands. Both cases resolve to an
 * MDTStringCodecInterface implementation via codec(), so callers never need to instanceof-check or
 * import a concrete codec class directly.
 */
enum MDTStringFormat
{
    case Legacy;
    case MDT2;

    public function codec(): MDTStringCodecInterface
    {
        return match ($this) {
            self::Legacy => new LegacyMDTCodec(),
            self::MDT2   => new MDT2Codec(),
        };
    }

    /**
     * Detects which format $string is encoded in for dispatch purposes. Legacy is the fallback for
     * anything that is not MDT2-prefixed - exactly like before this codec/format split existed,
     * every non-`!~MDT2~` string was handed to cli_weakauras_parser regardless of its shape, and
     * that fallback must keep working for real-world legacy strings this cheap check does not
     * otherwise recognize. This is deliberately a plausibility check, not a validity one - see
     * isValid() below for a real structural check per format.
     */
    public static function detect(string $string): self
    {
        return (new MDT2Codec())->appliesTo($string) ? self::MDT2 : self::Legacy;
    }

    /**
     * Checks whether $string is genuinely encoded in a supported MDT export-string format - unlike
     * detect(), which only exists to pick an implementation and always returns something. For the
     * MDT2 format this actually attempts to decode the payload (base64 + deflate + CBOR), since a
     * string can start with the `!~MDT2~` prefix and still be garbage - merely checking the prefix
     * (or "is this valid base64") is not a thorough check. For the legacy format, this uses the same
     * character-class check LegacyMDTCodec::appliesTo() does - a full validity check would mean
     * shelling out to cli_weakauras_parser, which is too expensive for what is ultimately just a
     * "is this worth reporting" heuristic.
     */
    public static function isValid(string $string): bool
    {
        $mdt2 = new MDT2Codec();

        if ($mdt2->appliesTo($string)) {
            try {
                $mdt2->decode($string);

                return true;
            } catch (Throwable) {
                return false;
            }
        }

        return (new LegacyMDTCodec())->appliesTo($string);
    }
}
