<?php

namespace Tests\Unit\App\Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('CustomHelper')]
class CustomHelperTest extends TestCase
{
    #[Test]
    #[DataProvider('abbreviateNumber_dataProvider')]
    public function abbreviateNumber_givenNumber_returnsAbbreviatedString(int $number, string $expected): void
    {
        // Act
        $result = abbreviateNumber($number);

        // Assert
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function abbreviateNumber_dataProvider(): array
    {
        return [
            'below thousand'                           => [999, '999'],
            'exact thousand'                           => [1000, '1K'],
            'thousands with fraction'                  => [6800, '6.8K'],
            'rounds up to 1000K without a stray comma' => [999_950, '1000K'],
            'exact million'                            => [1000000, '1M'],
            'millions with fraction'                   => [2500000, '2.5M'],
        ];
    }

    #[Test]
    #[DataProvider('initials_dataProvider')]
    public function initials_givenName_returnsAtMostTwoUppercaseLetters(string $name, string $expected): void
    {
        // Act
        $result = initials($name);

        // Assert
        $this->assertEquals($expected, $result);
        $this->assertLessThanOrEqual(2, mb_strlen($result, 'UTF-8'));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function initials_dataProvider(): array
    {
        return [
            'empty name'             => ['', ''],
            'whitespace only'        => ['   ', ''],
            'single letter'          => ['W', 'W'],
            'single word'            => ['Wotuu', 'WO'],
            'two words'              => ['John Doe', 'JD'],
            'three words'            => ['John Fitzgerald Doe', 'JD'],
            'many words'             => ['A Very Long Display Name', 'AN'],
            'surrounding whitespace' => ['  John Doe  ', 'JD'],
            'repeated whitespace'    => ["John\t \nDoe", 'JD'],
            'multibyte characters'   => ['Ünter Ötzi', 'ÜÖ'],
            'multibyte single word'  => ['Ünter', 'ÜN'],
        ];
    }
}
