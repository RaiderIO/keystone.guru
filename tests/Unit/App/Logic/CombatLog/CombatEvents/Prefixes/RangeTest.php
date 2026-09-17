<?php

namespace Tests\Unit\App\Logic\CombatLog\CombatEvents\Prefixes;

use App\Logic\CombatLog\CombatEvents\Prefixes\Range;
use App\Logic\CombatLog\CombatLogVersion;
use App\Models\Spell\SpellSchool;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('CombatLog')]
#[Group('Range')]
final class RangeTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('getSpellSchoolMask_givenALoggedSchool_returnsTheMask_DataProvider')]
    public function getSpellSchoolMask_givenALoggedSchool_returnsTheMask(string $loggedSchool, int $expectedMask): void
    {
        // Arrange
        $range = new Range(CombatLogVersion::RETAIL_11_0_5);
        $range->setParameters([12345, 'Test Spell', $loggedSchool]);

        // Act
        $mask = $range->getSpellSchoolMask();

        // Assert
        $this->assertSame($expectedMask, $mask);
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function getSpellSchoolMask_givenALoggedSchool_returnsTheMask_DataProvider(): array
    {
        return [
            // How retail actually logs the prefix school - a plain (int) cast reads every one of these as 0
            'hex physical'         => ['0x1', SpellSchool::Physical->value],
            'hex holy'             => ['0x2', SpellSchool::Holy->value],
            'hex shadow'           => ['0x20', SpellSchool::Shadow->value],
            'hex arcane'           => ['0x40', SpellSchool::Arcane->value],
            'hex uppercase'        => ['0X20', SpellSchool::Shadow->value],
            'hex multi school'     => ['0x7c', 124],
            'hex uppercase digits' => ['0x7C', 124],
            'hex none'             => ['0x0', 0],
            // Decimal is how the damage *suffix* logs it, and is accepted so the parse survives a format change
            'decimal'      => ['32', SpellSchool::Shadow->value],
            'decimal zero' => ['0', 0],
            'nil'          => ['nil', 0],
        ];
    }
}
