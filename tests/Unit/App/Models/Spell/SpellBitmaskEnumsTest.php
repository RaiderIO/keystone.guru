<?php

namespace Tests\Unit\App\Models\Spell;

use App\Models\Spell\SpellCounter;
use App\Models\Spell\SpellImmunity;
use App\Models\Spell\SpellMissType;
use App\Models\Spell\SpellSchool;
use App\Models\Traits\BitmaskEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The bits are stored in the `spells` mask columns and the slugs are translation-key suffixes and map context
 * data, so both - and their order, which drives dropdowns and readable strings - must never move.
 */
#[Group('Models')]
final class SpellBitmaskEnumsTest extends PublicTestCase
{
    /**
     * @param class-string<SpellSchool|SpellMissType|SpellCounter|SpellImmunity> $enumClass
     * @param array<int, string>                                                 $expected
     */
    #[Test]
    #[DataProvider('bitmaskEnumProvider')]
    public function slugsByBit_givenEnum_returnsTheStoredBitsAndSlugsInOrder(string $enumClass, array $expected): void
    {
        // Arrange - nothing to arrange, the mapping is static

        // Act
        $slugsByBit = $enumClass::slugsByBit();

        // Assert
        $this->assertSame($expected, $slugsByBit);
    }

    /**
     * @param class-string<SpellSchool|SpellMissType|SpellCounter|SpellImmunity> $enumClass
     * @param array<int, string>                                                 $expected
     */
    #[Test]
    #[DataProvider('bitmaskEnumProvider')]
    public function cases_givenEnum_eachOccupyASingleDistinctBit(string $enumClass, array $expected): void
    {
        // Arrange
        $combined = 0;

        foreach ($enumClass::cases() as $case) {
            // Act
            $setBitCount = substr_count(decbin($case->value), '1');

            // Assert
            $this->assertSame(1, $setBitCount, sprintf('%s::%s is not a single bit', $enumClass, $case->name));
            $this->assertSame(0, $combined & $case->value, sprintf('%s::%s shares its bit with another case', $enumClass, $case->name));
            $combined |= $case->value;
        }
    }

    /**
     * @return array<string, array{class-string, array<int, string>}>
     */
    public static function bitmaskEnumProvider(): array
    {
        return [
            'schools' => [
                SpellSchool::class,
                [
                    1  => 'physical',
                    2  => 'holy',
                    4  => 'fire',
                    8  => 'nature',
                    16 => 'frost',
                    32 => 'shadow',
                    64 => 'arcane',
                ],
            ],
            'miss types' => [
                SpellMissType::class,
                [
                    1    => 'absorb',
                    2    => 'block',
                    4    => 'deflect',
                    8    => 'dodge',
                    16   => 'evade',
                    32   => 'immune',
                    64   => 'miss',
                    128  => 'parry',
                    256  => 'reflect',
                    512  => 'resist',
                    1024 => 'interrupt',
                ],
            ],
            'counters' => [
                SpellCounter::class,
                [
                    1  => 'vanish',
                    2  => 'shadowmeld',
                    4  => 'feign_death',
                    8  => 'invisibility',
                    16 => 'cloak_of_shadows',
                ],
            ],
            'immunities' => [
                SpellImmunity::class,
                [
                    1  => 'divine_shield',
                    2  => 'ice_block',
                    4  => 'aspect_of_the_turtle',
                    8  => 'blessing_of_protection',
                    16 => 'blessing_of_spellwarding',
                    32 => 'anti_magic_shell',
                ],
            ],
        ];
    }

    /**
     * {@see BitmaskEnum::isSetIn()}
     */
    #[Test]
    public function isSetIn_givenMaskContainingTheBit_returnsTrue(): void
    {
        // Arrange
        $mask = SpellSchool::Fire->value | SpellSchool::Shadow->value;

        // Act
        $isSet = SpellSchool::Shadow->isSetIn($mask);

        // Assert
        $this->assertTrue($isSet);
    }

    #[Test]
    public function isSetIn_givenMaskWithoutTheBit_returnsFalse(): void
    {
        // Arrange
        $mask = SpellSchool::Fire->value | SpellSchool::Shadow->value;

        // Act
        $isSet = SpellSchool::Frost->isSetIn($mask);

        // Assert
        $this->assertFalse($isSet);
    }

    #[Test]
    public function isSetIn_givenEmptyMask_returnsFalse(): void
    {
        // Arrange
        $mask = 0;

        // Act
        $isSet = SpellCounter::Vanish->isSetIn($mask);

        // Assert
        $this->assertFalse($isSet);
    }
}
