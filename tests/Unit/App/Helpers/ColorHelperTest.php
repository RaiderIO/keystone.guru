<?php

namespace Tests\Unit\App\Helpers;

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
}
