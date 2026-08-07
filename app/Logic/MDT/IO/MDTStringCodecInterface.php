<?php

namespace App\Logic\MDT\IO;

use Throwable;

/**
 * A codec for one of the MDT export-string formats (see MDTStringFormat for the supported set).
 * Both the legacy `!...` format (LegacyMDTCodec) and the MDT 6.2+ `!~MDT2~` CBOR format (MDT2Codec)
 * implement this so callers can select an implementation without caring which format they hold -
 * and so the legacy implementation can eventually be deleted wholesale once MDT drops support for
 * it, without touching anything that depends on this interface.
 */
interface MDTStringCodecInterface
{
    /**
     * Cheap plausibility check: does $string look like it is encoded in this codec's format? This
     * is a dispatch hint, not a validity guarantee - a string this returns true for can still fail
     * to decode() (see MDTStringFormat::isValid(), which is built directly on this method across
     * every format).
     */
    public function appliesTo(string $string): bool;

    /**
     * @param  array<int|string, mixed> $contents The preset as a PHP array, in the shape decode() produces
     * @return string                   The full encoded export string
     * @throws Throwable                On encode failure
     */
    public function encode(array $contents): string;

    /**
     * @return array<int|string, mixed> The decoded preset
     * @throws Throwable                On decode failure
     */
    public function decode(string $string): array;
}
