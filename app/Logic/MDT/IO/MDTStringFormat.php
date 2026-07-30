<?php

namespace App\Logic\MDT\IO;

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
     * isValid() below for a cheap, still-not-a-decode check across both formats.
     */
    public static function detect(string $string): self
    {
        return (new MDT2Codec())->appliesTo($string) ? self::MDT2 : self::Legacy;
    }

    /**
     * Checks whether $string plausibly claims to be one of the MDT export-string formats - unlike
     * detect(), which only exists to pick an implementation and always returns something, this can
     * say no. This is deliberately just each codec's own appliesTo() (prefix check for MDT2,
     * character-class check for legacy) - not a full decode. Fully decoding here to "really" verify
     * would mean paying that cost on every call for what is ultimately just an "is this worth
     * reporting" heuristic (its only caller decides whether an unexpected decode failure elsewhere
     * is worth a report() call), and would mean decoding valid strings twice.
     */
    public static function isValid(string $string): bool
    {
        foreach (self::cases() as $format) {
            if ($format->codec()->appliesTo($string)) {
                return true;
            }
        }

        return false;
    }
}
