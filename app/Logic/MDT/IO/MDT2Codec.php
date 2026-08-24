<?php

namespace App\Logic\MDT\IO;

use App\Logic\MDT\Exception\MDT2DecodeException;
use App\Logic\MDT\Exception\MDT2EncodeException;
use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\Decoder;
use CBOR\ListObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\OtherObject\DoublePrecisionFloatObject;
use CBOR\OtherObject\FalseObject;
use CBOR\OtherObject\HalfPrecisionFloatObject;
use CBOR\OtherObject\NullObject;
use CBOR\OtherObject\SinglePrecisionFloatObject;
use CBOR\OtherObject\TrueObject;
use CBOR\StringStream;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use Throwable;

/**
 * Codec for the MDT 6.2+ `!~MDT2~` export-string format: PREFIX . Base64(RawDeflate(CBOR(preset))).
 *
 * MDT builds these strings in-game with Blizzard's C_EncodingUtil (SerializeCBOR +
 * CompressString(Deflate) + EncodeBase64), so this codec mirrors that API's documented behavior:
 * - Compression is a raw DEFLATE stream (RFC 1951), not zlib/gzip wrapped.
 * - Lua strings are always serialized as CBOR byte strings (major type 2), never text strings.
 * - Dense Lua sequences become CBOR arrays; sparse/mixed-key tables become CBOR maps.
 * - DeserializeCBOR in the WoW client rejects indefinite-length items and tags (major type 6),
 *   so encode() only ever emits definite-length, untagged items.
 *
 * decode() returns the same array shape the legacy path (cli_weakauras_parser JSON output ran
 * through json_decode) produces: dense Lua sequences arrive as 0-based PHP lists (JSON arrays),
 * while sparse/mixed-key Lua tables arrive as arrays with their original (1-based) integer keys
 * (JSON objects). CBOR arrays/maps line up with that split exactly, so CBOR arrays decode to plain
 * 0-based lists - downstream importers depend on this shape (e.g. ObjectImporter discriminates
 * dense vs sparse object details via isset($d[0])).
 */
final class MDT2Codec implements MDTStringCodecInterface
{
    public const PREFIX = '!~MDT2~';

    /**
     * Cap on the decompressed payload size to guard the public import endpoint against deflate
     * bombs. Real MDT strings decompress to a few dozen KB, so 1 MiB is ample headroom.
     */
    private const MAX_DECOMPRESSED_BYTES = 1048576;

    /**
     * Cap on the CBOR nesting depth. The WoW client itself refuses to (de)serialize beyond 100
     * levels; more importantly, cbor-php's decoder recurses per level and would exhaust the C
     * stack (segfault, uncatchable) on a deeply nested crafted payload without this pre-check.
     */
    private const MAX_DEPTH = 128;

    /**
     * Cap on the total CBOR item count - cbor-php materializes an object per item, so an
     * unbounded flat payload would exhaust PHP's memory_limit from a KB-scale request.
     */
    private const MAX_ITEMS = 262144;

    /**
     * Cap on how many trailing characters decodeBase64WithTrailingGarbageRecovery() will try
     * stripping from an otherwise-invalid payload before giving up - see that method's docblock
     * for why this is safe.
     */
    private const MAX_TRAILING_GARBAGE_BYTES = 8;

    /**
     * @param string $string A (potential) MDT export string, e.g. pasted user input
     */
    public function appliesTo(string $string): bool
    {
        return str_starts_with(trim($string), self::PREFIX);
    }

    /**
     * @param  array<int|string, mixed> $contents The preset as a PHP array, in the shape decode() produces
     * @return string                   The full `!~MDT2~...` export string
     * @throws MDT2EncodeException
     */
    public function encode(array $contents): string
    {
        $deflated = gzdeflate((string)self::toCborObject($contents), 9);

        if ($deflated === false) {
            throw new MDT2EncodeException('Unable to deflate CBOR payload');
        }

        return sprintf('%s%s', self::PREFIX, base64_encode($deflated));
    }

    /**
     * @return array<int|string, mixed> The preset in the same shape the legacy decode path produces
     * @throws MDT2DecodeException
     */
    public function decode(string $string): array
    {
        $string = trim($string);

        if (!str_starts_with($string, self::PREFIX)) {
            throw new MDT2DecodeException(sprintf('String does not start with %s', self::PREFIX));
        }

        $payload = substr($string, strlen(self::PREFIX));

        // Production has seen pastes containing a second (sometimes a byte-for-byte duplicate)
        // `!~MDT2~...` export concatenated after the first, with anything from nothing to a chunk
        // of unrelated text in between - a doubled paste action, or a whole multi-route note copied
        // instead of a single export. `!` and `~` fall outside the Base64 alphabet, so a genuine
        // export's payload can never legitimately contain the prefix - any occurrence past the
        // first is unambiguously the start of a second export, never a false positive. Try decoding
        // just the first export before falling through to the single-payload attempt below.
        $secondExportPosition = strpos($payload, self::PREFIX);
        if ($secondExportPosition !== false) {
            try {
                return self::decodeBinary(self::decodeBase64WithTrailingGarbageRecovery(rtrim(substr($payload, 0, $secondExportPosition))));
            } catch (MDT2DecodeException) {
                // Not recoverable this way either - fall through to the single-payload attempt
                // below, whose failure is what actually gets reported to the caller.
            }
        }

        return self::decodeBinary(self::decodeBase64WithTrailingGarbageRecovery($payload));
    }

    /**
     * Production has seen otherwise-valid MDT2 payloads with a handful of stray bytes stuck to the
     * end (e.g. a validly-padded Base64 blob with "w==" appended) - clipboard interference between
     * export and paste, outside our control. Only Base64's strict length/alphabet check can be
     * broken by such trailing bytes - gzinflate() already stops at the DEFLATE stream's end and
     * ignores anything after it, so a payload that would fail later in the pipeline fails there
     * regardless of trimming. Recovery is scoped to this step alone: each retry is a cheap
     * base64_decode() call, not a repeat of the expensive inflate/CBOR validation in
     * decodeBinary(), which still runs exactly once either way.
     *
     * @throws MDT2DecodeException
     */
    private static function decodeBase64WithTrailingGarbageRecovery(string $payload): string
    {
        for ($trim = 0; $trim <= self::MAX_TRAILING_GARBAGE_BYTES; $trim++) {
            $binary = base64_decode($trim === 0 ? $payload : substr($payload, 0, -$trim), true);

            if ($binary !== false) {
                return $binary;
            }
        }

        throw new MDT2DecodeException('Unable to decode Base64 payload');
    }

    /**
     * @return array<int|string, mixed>
     * @throws MDT2DecodeException
     */
    private static function decodeBinary(string $binary): array
    {
        $inflated = @gzinflate($binary, self::MAX_DECOMPRESSED_BYTES);

        if ($inflated === false) {
            throw new MDT2DecodeException('Unable to inflate payload (not a raw DEFLATE stream, or too large)');
        }

        self::assertSafeCborPayload($inflated);

        try {
            $cborObject = Decoder::create()->decode(StringStream::create($inflated));
        } catch (Throwable $throwable) {
            throw new MDT2DecodeException(sprintf('Unable to parse CBOR payload: %s', $throwable->getMessage()), 0, $throwable);
        }

        $decoded = self::fromCborObject($cborObject);

        if (!is_array($decoded)) {
            throw new MDT2DecodeException('CBOR payload root is not a map');
        }

        return $decoded;
    }

    /**
     * Note: we deliberately do NOT use cbor-php's normalize() on the tree - it returns integers as
     * base-10 strings (via brick/math), which would lose the int/string distinction downstream.
     *
     * @return array<int|string, mixed>|string|int|float|bool|null
     * @throws MDT2DecodeException
     */
    private static function fromCborObject(CBORObject $cborObject): array|string|int|float|bool|null
    {
        if ($cborObject instanceof ListObject) {
            $result = [];
            // 0-based, mirroring what json_decode produces for the legacy path's JSON arrays
            foreach ($cborObject as $item) {
                $result[] = self::fromCborObject($item);
            }

            return $result;
        }

        if ($cborObject instanceof MapObject) {
            $result = [];
            /** @var MapItem $mapItem */
            foreach ($cborObject as $mapItem) {
                $result[self::keyFromCborObject($mapItem->getKey())] = self::fromCborObject($mapItem->getValue());
            }

            return $result;
        }

        return match (true) {
            $cborObject instanceof UnsignedIntegerObject,
            $cborObject instanceof NegativeIntegerObject => self::intFromCborObject($cborObject),
            // Blizzard emits byte strings; accept text strings too, mirroring DeserializeCBOR's leniency
            $cborObject instanceof ByteStringObject,
            $cborObject instanceof TextStringObject => $cborObject->getValue(),
            $cborObject instanceof HalfPrecisionFloatObject,
            $cborObject instanceof SinglePrecisionFloatObject,
            $cborObject instanceof DoublePrecisionFloatObject => (float)$cborObject->normalize(),
            $cborObject instanceof TrueObject                 => true,
            $cborObject instanceof FalseObject                => false,
            $cborObject instanceof NullObject                 => null,
            // Tags, indefinite-length items, undefined, etc. - the WoW client never emits these
            default => throw new MDT2DecodeException(sprintf('Unsupported CBOR item: %s', $cborObject::class)),
        };
    }

    /**
     * Note: float map keys are rejected here (they surface as float objects) - the legacy JSON
     * path would have turned them into string keys, but real presets never use them and the WoW
     * client itself cannot round-trip them faithfully either.
     *
     * @throws MDT2DecodeException
     */
    private static function keyFromCborObject(CBORObject $cborObject): int|string
    {
        return match (true) {
            $cborObject instanceof UnsignedIntegerObject,
            $cborObject instanceof NegativeIntegerObject => self::intFromCborObject($cborObject),
            $cborObject instanceof ByteStringObject,
            $cborObject instanceof TextStringObject => $cborObject->getValue(),
            default                                 => throw new MDT2DecodeException(sprintf('Unsupported CBOR map key: %s', $cborObject::class)),
        };
    }

    /**
     * @throws MDT2DecodeException When the value does not fit in a PHP int (a bare (int) cast
     *                             would silently saturate to PHP_INT_MAX on crafted uint64 input)
     */
    private static function intFromCborObject(UnsignedIntegerObject|NegativeIntegerObject $cborObject): int
    {
        $value = $cborObject->getValue();

        if ((string)(int)$value !== $value) {
            throw new MDT2DecodeException(sprintf('CBOR integer out of range: %s', $value));
        }

        return (int)$value;
    }

    /**
     * Validates the raw CBOR payload iteratively before it is handed to cbor-php: its decoder
     * recurses once per nesting level (a deeply nested payload would exhaust the C stack - an
     * uncatchable segfault) and materializes an object per item with no ceiling. This walk only
     * reads item headers, so it is allocation-free and enforces MAX_DEPTH/MAX_ITEMS up front.
     * It also rejects indefinite-length items and trailing garbage, neither of which the WoW
     * client produces.
     *
     * @throws MDT2DecodeException
     */
    private static function assertSafeCborPayload(string $bytes): void
    {
        $length    = strlen($bytes);
        $position  = 0;
        $itemCount = 0;
        /** @var array<int, int|float> $stack Remaining direct children per open container */
        $stack = [];

        do {
            if ($position >= $length) {
                throw new MDT2DecodeException('Truncated CBOR payload');
            }

            $initialByte    = ord($bytes[$position++]);
            $majorType      = $initialByte >> 5;
            $additionalInfo = $initialByte & 0x1F;

            if ($additionalInfo === 31) {
                throw new MDT2DecodeException('Indefinite-length CBOR items are not supported');
            }

            if ($additionalInfo > 27) {
                throw new MDT2DecodeException('Invalid CBOR additional information');
            }

            $value = $additionalInfo;
            if ($additionalInfo >= 24) {
                // For major type 7 these bytes are the float/simple payload rather than a length -
                // the byte count consumed is identical, and $value goes unused there
                $extraBytes = 1 << ($additionalInfo - 24);
                if ($position + $extraBytes > $length) {
                    throw new MDT2DecodeException('Truncated CBOR payload');
                }

                $value = 0;
                for ($i = 0; $i < $extraBytes; $i++) {
                    $value = $value * 256 + ord($bytes[$position++]);
                }
            }

            if (++$itemCount > self::MAX_ITEMS) {
                throw new MDT2DecodeException(sprintf('CBOR payload exceeds %d items', self::MAX_ITEMS));
            }

            // This item is one child of the currently open container (if any)
            $openContainers = count($stack);
            if ($openContainers > 0) {
                $stack[$openContainers - 1]--;
            }

            switch ($majorType) {
                case 2: // byte string
                case 3: // text string
                    if ($position + $value > $length) {
                        throw new MDT2DecodeException('Truncated CBOR payload');
                    }

                    $position += $value;
                    break;
                case 4: // array
                case 5: // map
                case 6: // tag (fromCborObject rejects it later; walk it correctly here)
                    $children = match ($majorType) {
                        4       => $value,
                        5       => $value * 2,
                        default => 1,
                    };

                    if ($children > 0) {
                        if ($openContainers >= self::MAX_DEPTH) {
                            throw new MDT2DecodeException(sprintf('CBOR payload exceeds %d nesting levels', self::MAX_DEPTH));
                        }

                        $stack[] = $children;
                    }

                    break;
                default: // ints (0/1) and floats/simple values (7) carry no further payload
                    break;
            }

            while ($stack !== [] && end($stack) === 0) {
                array_pop($stack);
            }
        } while ($stack !== []);

        if ($position !== $length) {
            throw new MDT2DecodeException('Trailing bytes after CBOR payload');
        }
    }

    /**
     * @throws MDT2EncodeException
     */
    private static function toCborObject(mixed $value): CBORObject
    {
        if (is_array($value)) {
            // Empty Lua tables are ambiguous (array or map). Blizzard's SerializeCBOR resolves that
            // ambiguity towards an empty ARRAY (0x80) - verified against the real 12.1 PTR strings in
            // MDTImportStringServiceMDT2AuthoritativeTest, which asserts that re-encoding a decoded
            // real preset reproduces the client's payload byte for byte. Both decoders turn either
            // representation back into an empty table, so this only matters for that fidelity.
            if ($value === []) {
                return ListObject::create();
            }

            if (self::isDenseSequence($value)) {
                $listObject = ListObject::create();
                foreach ($value as $item) {
                    $listObject->add(self::toCborObject($item));
                }

                return $listObject;
            }

            $mapObject = MapObject::create();
            foreach ($value as $key => $item) {
                $mapObject->add(
                    is_int($key) ? self::intToCborObject($key) : ByteStringObject::create($key),
                    self::toCborObject($item),
                );
            }

            return $mapObject;
        }

        return match (true) {
            is_int($value) => self::intToCborObject($value),
            // Blizzard serializes Lua strings as byte strings (major type 2), never text strings
            is_string($value) => ByteStringObject::create($value),
            // Lua numbers are doubles; the client's DeserializeCBOR reads all float widths
            is_float($value) => DoublePrecisionFloatObject::createFromFloat($value),
            is_bool($value)  => $value ? TrueObject::create() : FalseObject::create(),
            $value === null  => NullObject::create(),
            default          => throw new MDT2EncodeException(sprintf('Unsupported value type: %s', get_debug_type($value))),
        };
    }

    /**
     * UnsignedIntegerObject::create()/NegativeIntegerObject::create() pick their header width with
     * an off-by-one (`isLessThan(0xFF)` etc. instead of `isLessThanOrEqualTo`), so e.g. 255 would
     * round-trip correctly but encode one byte wider than CBOR's canonical minimal-length form -
     * not a bug MDT's DeserializeCBOR would notice, but one the byte-for-byte fidelity claim in this
     * class's docblock does not survive. Compute the minimal width ourselves and call
     * createObjectForValue() directly instead of going through create().
     *
     * @throws MDT2EncodeException
     */
    private static function intToCborObject(int $value): CBORObject
    {
        // CBOR negative integers are encoded as -(magnitude + 1), i.e. magnitude = -1 - value
        $magnitude = $value >= 0 ? $value : -1 - $value;

        [$additionalInformation, $data] = match (true) {
            $magnitude < 24          => [$magnitude, null],
            $magnitude <= 0xFF       => [24, pack('C', $magnitude)],
            $magnitude <= 0xFFFF     => [25, pack('n', $magnitude)],
            $magnitude <= 0xFFFFFFFF => [26, pack('N', $magnitude)],
            // cbor-php caps basic int encoding at 32 bits and wants a big-integer tag beyond that,
            // but the WoW client rejects tags - fail loudly instead
            default => throw new MDT2EncodeException(sprintf('Unable to encode integer %d: value exceeds the 32-bit range this codec supports', $value)),
        };

        return $value >= 0
            ? UnsignedIntegerObject::createObjectForValue($additionalInformation, $data)
            : NegativeIntegerObject::createObjectForValue($additionalInformation, $data);
    }

    /**
     * A dense sequence (a plain 0-based PHP list, as decode() produces, or a dense 1-based array -
     * both represent the same dense Lua sequence) is encoded as a CBOR array; anything else becomes
     * a CBOR map.
     *
     * @param array<int|string, mixed> $value Must not be empty
     */
    private static function isDenseSequence(array $value): bool
    {
        return array_is_list($value) || array_keys($value) === range(1, count($value));
    }
}
