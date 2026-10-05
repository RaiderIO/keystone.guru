<?php

namespace Tests\Unit\App\Logic\MDT\IO;

use App\Logic\MDT\Exception\LegacyMDTDecodeException;
use App\Logic\MDT\IO\LegacyMDTCodec;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('MDT')]
#[Group('LegacyMDTCodec')]
final class LegacyMDTCodecTest extends TestCase
{
    private LegacyMDTCodec $codec;

    protected function setUp(): void
    {
        parent::setUp();

        $this->codec = new LegacyMDTCodec();
    }

    #[Test]
    #[Group('UsesLua')]
    public function decode_givenRealFixture_returnsArray(): void
    {
        // Arrange
        $string = file_get_contents(base_path('tests/Feature/App/Service/MDT/Fixtures/mdt_import_v507_mistsoftirnescithe.txt'));

        // Act
        $decoded = $this->codec->decode($string);

        // Assert
        $this->assertArrayHasKey('value', $decoded);
        $this->assertNotEmpty($decoded['value']);
        $this->assertSame('Default', $decoded['text'] ?? null);
        $this->assertEquals(1, $decoded['week'] ?? null);
        $this->assertEquals(31, $decoded['value']['currentDungeonIdx']);
        $this->assertCount(10, $decoded['value']['pulls']);
    }

    #[Test]
    #[Group('UsesLua')]
    public function decode_givenEncodedPreset_roundTripsIt(): void
    {
        // Arrange
        $preset = [
            'text'  => 'Round trip',
            'week'  => 3,
            'value' => ['currentDungeonIdx' => 17, 'teeming' => false],
        ];
        $string = $this->codec->encode($preset);

        // Act
        $decoded = $this->codec->decode($string);

        // Assert
        $this->assertTrue($this->codec->appliesTo($string));
        $this->assertEquals($preset, $decoded);
    }

    #[Test]
    #[Group('UsesLua')]
    public function decode_givenCorruptString_throwsLegacyMDTDecodeException(): void
    {
        // Assert - cli_weakauras_parser's own error becomes the message
        $this->expectException(LegacyMDTDecodeException::class);
        $this->expectExceptionMessage('Failed to decompress');

        // Act
        $this->codec->decode('!garbage');
    }

    #[Test]
    #[Group('UsesLua')]
    public function decode_givenDecodedOutputOverSizeLimit_throwsLegacyMDTDecodeException(): void
    {
        // Arrange - a highly repetitive payload encodes to a small string but decodes past the limit
        $string = $this->codec->encode(['text' => str_repeat('a', LegacyMDTCodec::MAX_DECODED_BYTES + 1)]);

        // Assert
        $this->expectException(LegacyMDTDecodeException::class);
        $this->expectExceptionMessage('exceeds');

        // Act
        $this->codec->decode($string);
    }

    #[Test]
    #[DataProvider('appliesTo_givenString_returnsExpectedResult_Provider')]
    public function appliesTo_givenString_returnsExpectedResult(string $string, bool $expected): void
    {
        // Act
        $result = $this->codec->appliesTo($string);

        // Assert
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function appliesTo_givenString_returnsExpectedResult_Provider(): array
    {
        return [
            'legacy deflate string'      => ['!fBcBcAWnPXhz(abc)', true],
            'legacy string + whitespace' => ["  \n!fBcBcAWnPXhz\n ", true],
            'legacy string with padding' => ['!fBcBcAWnPXhz==', true],
            // The `~` in the MDT2 prefix is deliberately outside this character class
            'mdt2 prefix'                     => ['!~MDT2~c29tZXRoaW5n', false],
            'missing exclamation mark'        => ['fBcBcAWnPXhz', false],
            'empty string'                    => ['', false],
            'disallowed character mid-string' => ['!fBcBc AWnPXhz', false],
        ];
    }
}
