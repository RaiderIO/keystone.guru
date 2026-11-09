<?php

namespace Tests\Unit\App\Helpers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('ColorHelper')]
class ColorHelperTest extends TestCase
{
    /** mt_srand() seed whose first randomHexColor() is #b83a08, a vermilion. */
    private const int SEED_FIRST_COLOR_VERMILION = 314;

    #[Test]
    public function randomHexColorNoMapColors_givenVermilionFirstDraw_returnsTheNextDraw(): void
    {
        try {
            // Arrange
            mt_srand(self::SEED_FIRST_COLOR_VERMILION);
            $firstDraw  = randomHexColor();
            $secondDraw = randomHexColor();
            $this->assertSame('vermilion', hex2name($firstDraw));
            $this->assertNotContains(hex2name($secondDraw), ['orange', 'brown', 'vermilion']);
            mt_srand(self::SEED_FIRST_COLOR_VERMILION);

            // Act
            $result = randomHexColorNoMapColors();

            // Assert
            $this->assertSame($secondDraw, $result);
        } finally {
            mt_srand();
        }
    }

    #[Test]
    #[DataProvider('isHexColorProvider')]
    public function isHexColor_givenColor_returnsExpectedResult(?string $color, bool $expected): void
    {
        // Act
        $result = isHexColor($color);

        // Assert
        $this->assertSame($expected, $result);
    }

    /** @return array<string, array{0: string|null, 1: bool}> */
    public static function isHexColorProvider(): array
    {
        return [
            'six digits'           => ['#12ab9F', true],
            'three digits'         => ['#abc', true],
            'upper case'           => ['#ABCDEF', true],
            'null'                 => [null, false],
            'empty'                => ['', false],
            'no hash'              => ['abcdef', false],
            'four digits'          => ['#abcd', false],
            'non hex digit'        => ['#abcdeg', false],
            'leading whitespace'   => [' #abcdef', false],
            'trailing newline'     => ["#abcdef\n", false],
            'named color'          => ['red', false],
            'css breaking payload' => ['#fff;}</style><script>x</script>', false],
        ];
    }
}
