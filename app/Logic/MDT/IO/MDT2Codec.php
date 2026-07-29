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
use InvalidArgumentException;
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
final class MDT2Codec
{
    public const PREFIX = '!~MDT2~';

    /** Cap on the decompressed payload size to guard the public import endpoint against deflate bombs */
    private const MAX_DECOMPRESSED_BYTES = 33554432;

    /**
     * @param string $string A (potential) MDT export string, e.g. pasted user input
     */
    public static function appliesTo(string $string): bool
    {
        return str_starts_with(trim($string), self::PREFIX);
    }

    /**
     * @param array<int|string, mixed> $contents The preset as a PHP array, in the shape decode() produces
     * @return string The full `!~MDT2~...` export string
     * @throws MDT2EncodeException
     */
    public static function encode(array $contents): string
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
    public static function decode(string $string): array
    {
        $string = trim($string);

        if (!str_starts_with($string, self::PREFIX)) {
            throw new MDT2DecodeException(sprintf('String does not start with %s', self::PREFIX));
        }

        $binary = base64_decode(substr($string, strlen(self::PREFIX)), true);

        if ($binary === false) {
            throw new MDT2DecodeException('Unable to decode Base64 payload');
        }

        $inflated = @gzinflate($binary, self::MAX_DECOMPRESSED_BYTES);

        if ($inflated === false) {
            throw new MDT2DecodeException('Unable to inflate payload (not a raw DEFLATE stream, or too large)');
        }

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
            $cborObject instanceof NegativeIntegerObject => (int)$cborObject->getValue(),
            // Blizzard emits byte strings; accept text strings too, mirroring DeserializeCBOR's leniency
            $cborObject instanceof ByteStringObject,
            $cborObject instanceof TextStringObject => $cborObject->getValue(),
            $cborObject instanceof HalfPrecisionFloatObject,
            $cborObject instanceof SinglePrecisionFloatObject,
            $cborObject instanceof DoublePrecisionFloatObject => (float)$cborObject->normalize(),
            $cborObject instanceof TrueObject  => true,
            $cborObject instanceof FalseObject => false,
            $cborObject instanceof NullObject  => null,
            // Tags, indefinite-length items, undefined, etc. - the WoW client never emits these
            default => throw new MDT2DecodeException(sprintf('Unsupported CBOR item: %s', $cborObject::class)),
        };
    }

    /**
     * @throws MDT2DecodeException
     */
    private static function keyFromCborObject(CBORObject $cborObject): int|string
    {
        return match (true) {
            $cborObject instanceof UnsignedIntegerObject,
            $cborObject instanceof NegativeIntegerObject => (int)$cborObject->getValue(),
            $cborObject instanceof ByteStringObject,
            $cborObject instanceof TextStringObject => $cborObject->getValue(),
            default => throw new MDT2DecodeException(sprintf('Unsupported CBOR map key: %s', $cborObject::class)),
        };
    }

    /**
     * @throws MDT2EncodeException
     */
    private static function toCborObject(mixed $value): CBORObject
    {
        if (is_array($value)) {
            // Empty Lua tables are ambiguous (array or map) - Blizzard and we both emit an empty map,
            // and both decoders turn either representation into an empty table/array anyway
            if ($value === []) {
                return MapObject::create();
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
                    self::toCborObject($item)
                );
            }

            return $mapObject;
        }

        return match (true) {
            is_int($value) => self::intToCborObject($value),
            // Blizzard serializes Lua strings as byte strings (major type 2), never text strings
            is_string($value) => ByteStringObject::create($value),
            // Lua numbers are doubles; the client's DeserializeCBOR reads all float widths
            is_float($value)  => DoublePrecisionFloatObject::createFromFloat($value),
            is_bool($value)   => $value ? TrueObject::create() : FalseObject::create(),
            $value === null   => NullObject::create(),
            default           => throw new MDT2EncodeException(sprintf('Unsupported value type: %s', get_debug_type($value))),
        };
    }

    /**
     * @throws MDT2EncodeException
     */
    private static function intToCborObject(int $value): CBORObject
    {
        try {
            return $value >= 0 ? UnsignedIntegerObject::create($value) : NegativeIntegerObject::create($value);
        } catch (InvalidArgumentException $invalidArgumentException) {
            // cbor-php caps basic int encoding at 32 bits and wants big-integer tags beyond that,
            // but the WoW client rejects tags - fail loudly instead
            throw new MDT2EncodeException(sprintf('Unable to encode integer %d: %s', $value, $invalidArgumentException->getMessage()), 0, $invalidArgumentException);
        }
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
