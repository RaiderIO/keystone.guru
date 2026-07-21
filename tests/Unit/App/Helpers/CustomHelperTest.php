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
}
