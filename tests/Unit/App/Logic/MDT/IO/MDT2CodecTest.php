<?php

namespace Tests\Unit\App\Logic\MDT\IO;

use App\Logic\MDT\Exception\MDT2DecodeException;
use App\Logic\MDT\Exception\MDT2EncodeException;
use App\Logic\MDT\IO\MDT2Codec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Tests\TestCase;

#[Group('MDT')]
#[Group('MDT2Codec')]
final class MDT2CodecTest extends TestCase
{
    #[Test]
    #[DataProvider('appliesTo_givenString_returnsExpectedResult_Provider')]
    public function appliesTo_givenString_returnsExpectedResult(string $string, bool $expected): void
    {
        // Act
        $result = MDT2Codec::appliesTo($string);

        // Assert
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function appliesTo_givenString_returnsExpectedResult_Provider(): array
    {
        return [
            'mdt2 prefix'              => ['!~MDT2~c29tZXRoaW5n', true],
            'mdt2 prefix + whitespace' => ["  \n!~MDT2~c29tZXRoaW5n\n ", true],
            'legacy deflate string'    => ['!fBcBcAWnPXhz(...)', false],
            'legacy compress string'   => ['fBcBcAWnPXhz', false],
            'empty string'             => ['', false],
            'prefix mid-string'        => ['x!~MDT2~abc', false],
        ];
    }

    #[Test]
    public function encode_givenStringKeyedMap_producesByteStringKeyedDefiniteLengthCbor(): void
    {
        // Act
        $encoded = MDT2Codec::encode(['a' => 1]);

        // Assert - {h'61': 1} = A1 (map, 1 pair) 41 61 (byte string 'a') 01 (uint 1)
        $this->assertEquals('a1416101', bin2hex($this->rawCbor($encoded)));
    }

    #[Test]
    public function encode_givenOneBasedDenseArray_emitsCborArray(): void
    {
        // Act
        $encoded = MDT2Codec::encode(['pulls' => [1 => 10, 2 => 20, 3 => 30]]);

        // Assert - map(1): 'pulls' => [10, 20, 30] (0x83 = array of 3)
        $this->assertEquals('a14570756c6c73830a14181e', bin2hex($this->rawCbor($encoded)));
    }

    #[Test]
    public function encode_givenZeroBasedPhpList_emitsCborArray(): void
    {
        // Act - a plain PHP list represents the same dense Lua sequence
        $encoded = MDT2Codec::encode(['pulls' => [10, 20, 30]]);

        // Assert - identical bytes to the 1-based variant
        $this->assertEquals('a14570756c6c73830a14181e', bin2hex($this->rawCbor($encoded)));
    }

    #[Test]
    public function encode_givenSparseIntKeys_emitsCborMapWithIntKeys(): void
    {
        // Act - gap at index 6, like MDT object details tables
        $encoded = MDT2Codec::encode([1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 7 => 7]);

        // Assert - A6 = map of 6 pairs, keys as CBOR ints (01..05, 07)
        $this->assertEquals('a6010102020303040405050707', bin2hex($this->rawCbor($encoded)));
    }

    #[Test]
    public function encode_givenMixedKeys_emitsCborMap(): void
    {
        // Act - shaped like an MDT pull table: array part + string hash part
        $encoded = MDT2Codec::encode([1 => 3, 2 => 5, 'color' => 'ff0000']);

        // Assert - A3 = map of 3 pairs; 45 'color' as byte string key, 46 'ff0000' as byte string value
        $this->assertEquals('a301030205'.'45636f6c6f72'.'46666630303030', bin2hex($this->rawCbor($encoded)));
    }

    #[Test]
    public function encode_givenEmptyArray_emitsEmptyMap(): void
    {
        // Act
        $encoded = MDT2Codec::encode(['selection' => []]);

        // Assert - A0 = empty map
        $this->assertEquals('a14973656c656374696f6ea0', bin2hex($this->rawCbor($encoded)));
    }

    #[Test]
    public function encode_givenScalarValues_emitsExpectedCborTypes(): void
    {
        // Act
        $encoded = MDT2Codec::encode([
            'float'    => 1.5,
            'true'     => true,
            'false'    => false,
            'null'     => null,
            'negative' => -3,
        ]);

        // Assert - FB 3FF8...=double 1.5, F5=true, F4=false, F6=null, 22=negint -3
        $this->assertEquals(
            'a545666c6f6174fb3ff80000000000004474727565f54566616c7365f4446e756c6cf6486e6567617469766522',
            bin2hex($this->rawCbor($encoded))
        );
    }

    #[Test]
    public function encode_givenIntBeyondUint32_throwsMDT2EncodeException(): void
    {
        // Assert
        $this->expectException(MDT2EncodeException::class);

        // Act
        MDT2Codec::encode(['value' => 0x1_0000_0000]);
    }

    #[Test]
    public function encode_givenObjectValue_throwsMDT2EncodeException(): void
    {
        // Assert
        $this->expectException(MDT2EncodeException::class);

        // Act
        MDT2Codec::encode(['value' => new stdClass()]);
    }

    #[Test]
    public function decode_givenEncodedPresetShapedArray_roundTripsExactly(): void
    {
        // Arrange - a realistic preset-shaped array in the exact shape the legacy decode path
        // produces: dense sequences as 0-based lists, sparse/mixed-key tables with 1-based int keys
        $preset = [
            'text'         => 'Test +30 route',
            'week'         => 3,
            'difficulty'   => 30,
            'addonVersion' => 620,
            'value'        => [
                'currentDungeonIdx' => 129,
                'currentPull'       => 1,
                'currentSublevel'   => 1,
                'teeming'           => false,
                'selection'         => [],
                'pulls'             => [
                    [1 => [5.0, 7.0], 2 => [4.0], 'color' => '4fb3ff'],
                    [1 => [9.0], 'color' => 'ff0000'],
                ],
                'riftOffsets'       => [],
            ],
            'objects'      => [
                [
                    // Dense details (0-based list) and sparse details (gap at 6, 1-based keys)
                    'd' => [51.5, 37.25, 1.0, true, 'ffffff', -8.0],
                ],
                [
                    'd' => [1 => 51.5, 2 => 37.25, 3 => 1.0, 4 => true, 5 => 'ffffff', 7 => 'a note'],
                ],
            ],
            'uid'          => 'aBcDeFgHiJ(',
        ];

        // Act
        $decoded = MDT2Codec::decode(MDT2Codec::encode($preset));

        // Assert - assertSame: keys, order, types and values must all survive the round trip
        $this->assertSame($preset, $decoded);
    }

    #[Test]
    public function decode_givenCborArray_returnsZeroBasedPhpList(): void
    {
        // Arrange - hand-built CBOR: {h'objects': [h'a', h'b']} - arrays must decode to 0-based lists,
        // exactly like json_decode does for the legacy path's JSON arrays
        $cbor = hex2bin('a1476f626a656374738241614162');

        // Act
        $decoded = MDT2Codec::decode($this->buildMdt2String($cbor));

        // Assert
        $this->assertSame(['objects' => ['a', 'b']], $decoded);
    }

    #[Test]
    public function decode_givenCborMapWithIntKeys_preservesOriginalKeys(): void
    {
        // Arrange - hand-built CBOR: {h'd': {1: 10, 7: 20}} - sparse tables keep their 1-based keys,
        // exactly like json_decode does for the legacy path's JSON objects
        $cbor = hex2bin('a14164a2010a0714');

        // Act
        $decoded = MDT2Codec::decode($this->buildMdt2String($cbor));

        // Assert
        $this->assertSame(['d' => [1 => 10, 7 => 20]], $decoded);
    }

    #[Test]
    public function decode_givenTextStringItems_treatsThemAsStrings(): void
    {
        // Arrange - hand-built CBOR with text strings (major type 3): {"key": "value"}
        $cbor = hex2bin('a1636b65796576616c7565');

        // Act
        $decoded = MDT2Codec::decode($this->buildMdt2String($cbor));

        // Assert
        $this->assertSame(['key' => 'value'], $decoded);
    }

    #[Test]
    public function decode_givenDuplicateMapKeys_lastValueWins(): void
    {
        // Arrange - hand-built CBOR: {h'a': 1, h'a': 2} - documents cbor-php's silent last-wins behavior
        $cbor = hex2bin('a2416101416102');

        // Act
        $decoded = MDT2Codec::decode($this->buildMdt2String($cbor));

        // Assert
        $this->assertSame(['a' => 2], $decoded);
    }

    #[Test]
    public function decode_givenCborTag_throwsMDT2DecodeException(): void
    {
        // Arrange - hand-built CBOR: {h'a': 0("2026-01-01")} - tag 0 (datetime), which the WoW client rejects too
        $cbor = hex2bin('a14161c06a323032362d30312d3031');

        // Assert
        $this->expectException(MDT2DecodeException::class);

        // Act
        MDT2Codec::decode($this->buildMdt2String($cbor));
    }

    #[Test]
    public function decode_givenIndefiniteLengthMap_throwsMDT2DecodeException(): void
    {
        // Arrange - hand-built CBOR: {_ h'a': 1} (indefinite-length map, BF...FF)
        $cbor = hex2bin('bf416101ff');

        // Assert
        $this->expectException(MDT2DecodeException::class);

        // Act
        MDT2Codec::decode($this->buildMdt2String($cbor));
    }

    #[Test]
    public function decode_givenNonMapRoot_throwsMDT2DecodeException(): void
    {
        // Arrange - hand-built CBOR: bare uint 42
        $cbor = hex2bin('182a');

        // Assert
        $this->expectException(MDT2DecodeException::class);

        // Act
        MDT2Codec::decode($this->buildMdt2String($cbor));
    }

    #[Test]
    #[DataProvider('decode_givenCorruptString_throwsMDT2DecodeException_Provider')]
    public function decode_givenCorruptString_throwsMDT2DecodeException(string $string): void
    {
        // Assert
        $this->expectException(MDT2DecodeException::class);

        // Act
        MDT2Codec::decode($string);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function decode_givenCorruptString_throwsMDT2DecodeException_Provider(): array
    {
        return [
            'no prefix'        => ['!fBcBcAWnPXhz'],
            'invalid base64'   => ['!~MDT2~%%%not-base64%%%'],
            'invalid deflate'  => [sprintf('!~MDT2~%s', base64_encode('this is not a deflate stream'))],
            'truncated cbor'   => [sprintf('!~MDT2~%s', base64_encode(gzdeflate("\xA5\x41\x61", 9)))],
        ];
    }

    /**
     * Extracts the raw CBOR bytes back out of a full `!~MDT2~...` string.
     */
    private function rawCbor(string $encoded): string
    {
        return gzinflate(base64_decode(substr($encoded, strlen(MDT2Codec::PREFIX))));
    }

    /**
     * Wraps raw CBOR bytes into a full `!~MDT2~...` string.
     */
    private function buildMdt2String(string $cbor): string
    {
        return sprintf('%s%s', MDT2Codec::PREFIX, base64_encode(gzdeflate($cbor, 9)));
    }
}
