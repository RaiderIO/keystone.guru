<?php

namespace App\Logic\MDT\IO;

use CBOR\ByteStringObject;
use CBOR\CBORObject;
use CBOR\DecoderInterface;
use CBOR\ListObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\Normalizable;
use CBOR\OtherObject\DoublePrecisionFloatObject;
use CBOR\OtherObject\FalseObject;
use CBOR\OtherObject\HalfPrecisionFloatObject;
use CBOR\OtherObject\NullObject;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\OtherObject\OtherObjectManagerInterface;
use CBOR\OtherObject\SimpleObject;
use CBOR\OtherObject\SinglePrecisionFloatObject;
use CBOR\OtherObject\TrueObject;
use CBOR\OtherObject\UndefinedObject;
use CBOR\Stream;
use CBOR\Tag\TagManager;
use CBOR\Tag\TagManagerInterface;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use InvalidArgumentException;

/**
 * Decodes definite-length CBOR like cbor-php's own Decoder, except that a map key repeated within one
 * map replaces the earlier entry instead of failing the whole payload. cbor-php's Decoder rejects such
 * maps through MapObject::add() and offers no switch for it; MapObject::set() replaces instead.
 *
 * Indefinite-length items are rejected. Nesting depth is not capped: callers must bound it before
 * decoding, as MDT2Codec's header pre-check does.
 */
final class LastValueWinsCborDecoder implements DecoderInterface
{
    private readonly TagManagerInterface $tagManager;

    private readonly OtherObjectManagerInterface $otherObjectManager;

    public function __construct()
    {
        $this->tagManager         = TagManager::create();
        $this->otherObjectManager = OtherObjectManager::create()
            ->add(SimpleObject::class)
            ->add(FalseObject::class)
            ->add(TrueObject::class)
            ->add(NullObject::class)
            ->add(UndefinedObject::class)
            ->add(HalfPrecisionFloatObject::class)
            ->add(SinglePrecisionFloatObject::class)
            ->add(DoublePrecisionFloatObject::class);
    }

    public function decode(Stream $stream): CBORObject
    {
        $initialByte    = ord($stream->read(1));
        $majorType      = $initialByte >> 5;
        $additionalInfo = $initialByte & 0x1F;

        if ($additionalInfo > CBORObject::LENGTH_8_BYTES) {
            throw new InvalidArgumentException(sprintf('Unsupported CBOR additional information %d', $additionalInfo));
        }

        $argument = $additionalInfo >= CBORObject::LENGTH_1_BYTE
            ? $stream->read(1 << ($additionalInfo - CBORObject::LENGTH_1_BYTE))
            : null;

        return match ($majorType) {
            CBORObject::MAJOR_TYPE_UNSIGNED_INTEGER => UnsignedIntegerObject::createObjectForValue($additionalInfo, $argument),
            CBORObject::MAJOR_TYPE_NEGATIVE_INTEGER => NegativeIntegerObject::createObjectForValue($additionalInfo, $argument),
            CBORObject::MAJOR_TYPE_BYTE_STRING      => ByteStringObject::create($stream->read(self::length($additionalInfo, $argument))),
            CBORObject::MAJOR_TYPE_TEXT_STRING      => TextStringObject::create($stream->read(self::length($additionalInfo, $argument))),
            CBORObject::MAJOR_TYPE_LIST             => $this->decodeList($stream, self::length($additionalInfo, $argument)),
            CBORObject::MAJOR_TYPE_MAP              => $this->decodeMap($stream, self::length($additionalInfo, $argument)),
            CBORObject::MAJOR_TYPE_TAG              => $this->tagManager->createObjectForValue($additionalInfo, $argument, $this->decode($stream)),
            default                                 => $this->otherObjectManager->createObjectForValue($additionalInfo, $argument),
        };
    }

    private function decodeList(Stream $stream, int $itemCount): ListObject
    {
        $listObject = ListObject::create();
        for ($i = 0; $i < $itemCount; $i++) {
            $listObject->add($this->decode($stream));
        }

        return $listObject;
    }

    private function decodeMap(Stream $stream, int $pairCount): MapObject
    {
        /** @var array<string, MapItem> $mapItems */
        $mapItems = [];
        for ($i = 0; $i < $pairCount; $i++) {
            $key   = $this->decode($stream);
            $value = $this->decode($stream);

            $mapItems[self::keyOffset($key)] = MapItem::create($key, $value);
        }

        $mapObject = MapObject::create();
        foreach ($mapItems as $mapItem) {
            $mapObject->set($mapItem);
        }

        return $mapObject;
    }

    /**
     * The PHP array offset a key lands on once normalized, regardless of its major type: 1, "1" and h'31' share
     * one offset, as do "a" and h'a'. Keys sharing an offset are one entry: it keeps the first key's position
     * and takes the last key and value, so a consumer that folds keys onto PHP array keys sees the final value.
     * Other keys (floats, simple values, containers, tags) are told apart by their encoded bytes.
     */
    private static function keyOffset(CBORObject $key): string
    {
        $isScalarKey = in_array($key->getMajorType(), [
            CBORObject::MAJOR_TYPE_UNSIGNED_INTEGER,
            CBORObject::MAJOR_TYPE_NEGATIVE_INTEGER,
            CBORObject::MAJOR_TYPE_BYTE_STRING,
            CBORObject::MAJOR_TYPE_TEXT_STRING,
        ], true);

        if ($isScalarKey && $key instanceof Normalizable) {
            return sprintf('s:%s', $key->normalize());
        }

        return sprintf('o:%s', $key);
    }

    private static function length(int $additionalInfo, ?string $argument): int
    {
        if ($argument === null) {
            return $additionalInfo;
        }

        $length = 0;
        foreach (str_split($argument) as $byte) {
            $length = ($length << 8) | ord($byte);
        }

        if ($length < 0) {
            throw new InvalidArgumentException('CBOR length out of range');
        }

        return $length;
    }
}
