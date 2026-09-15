<?php

namespace Tests\Unit\App\Models\Spell;

use App\Models\Spell\SpellSchool;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
final class SpellSchoolTest extends PublicTestCase
{
    #[Test]
    public function maskMagic_givenAllSchools_coversEveryNonPhysicalSchool(): void
    {
        // Arrange
        $expected = 2 | 4 | 8 | 16 | 32 | 64;

        // Act
        $mask = SpellSchool::MASK_MAGIC;

        // Assert
        $this->assertSame($expected, $mask);
        $this->assertFalse(SpellSchool::Physical->isSetIn($mask));
    }

    #[Test]
    public function maskAll_givenAllSchools_coversEverySchool(): void
    {
        // Arrange
        $expected = array_sum(array_column(SpellSchool::cases(), 'value'));

        // Act
        $mask = SpellSchool::MASK_ALL;

        // Assert
        $this->assertSame(127, $mask);
        $this->assertSame($expected, $mask);
    }
}
