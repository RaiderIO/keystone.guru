<?php

namespace Tests\Unit\App\Logic\MDT\IO;

use App\Logic\MDT\IO\MDTStringFormat;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('MDT')]
#[Group('MDTStringFormat')]
final class MDTStringFormatTest extends TestCase
{
    /** A real (if minimal) `!~MDT2~` string: `!~MDT2~` . Base64(RawDeflate(CBOR(empty map))). */
    private const string VALID_MDT2_STRING = '!~MDT2~WwAA';

    #[Test]
    #[DataProvider('detect_givenString_returnsExpectedFormat_Provider')]
    public function detect_givenString_returnsExpectedFormat(string $string, MDTStringFormat $expected): void
    {
        // Act
        $result = MDTStringFormat::detect($string);

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, MDTStringFormat}>
     */
    public static function detect_givenString_returnsExpectedFormat_Provider(): array
    {
        return [
            'mdt2 string'          => [self::VALID_MDT2_STRING, MDTStringFormat::MDT2],
            'legacy-shaped string' => ['!fBcBcAWnPXhz(...)', MDTStringFormat::Legacy],
            // detect() is a dispatch hint, not a validity check - legacy is the fallback for
            // anything not MDT2-prefixed, exactly like every string was handled before this split
            'garbage falls back to legacy'      => ['this_is_not_a_valid_mdt_string', MDTStringFormat::Legacy],
            'empty string falls back to legacy' => ['', MDTStringFormat::Legacy],
        ];
    }

    #[Test]
    #[DataProvider('isValid_givenString_returnsExpectedResult_Provider')]
    public function isValid_givenString_returnsExpectedResult(string $string, bool $expected): void
    {
        // Act
        $result = MDTStringFormat::isValid($string);

        // Assert
        $this->assertSame($expected, $result);
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function isValid_givenString_returnsExpectedResult_Provider(): array
    {
        return [
            'valid mdt2 string' => [self::VALID_MDT2_STRING, true],
            // isValid() is deliberately just a prefix/character-class check now, not a full decode
            // (see the isValid() docblock) - so a string carrying the MDT2 prefix is considered
            // valid here even though its payload is garbage. It genuinely claimed to be an MDT
            // export string, so it is still worth report()-ing if decode() later fails on it.
            'mdt2 prefix with non-base64 payload'  => ['!~MDT2~%%%not-base64%%%', true],
            'mdt2 prefix with non-deflate payload' => [sprintf('!~MDT2~%s', base64_encode('this is not a deflate stream')), true],
            'legacy-shaped string'                 => ['!fBcBcAWnPXhz(abc)', true],
            // What isValidBase64() got wrong: this is genuinely valid Base64, but it is not an MDT
            // string at all (no `!` prefix), so it must not be reported as one.
            'valid base64 that is not an mdt string' => ['aGVsbG8=', false],
            'random text'                            => ['this_is_not_a_valid_mdt_string', false],
            'empty string'                           => ['', false],
        ];
    }
}
