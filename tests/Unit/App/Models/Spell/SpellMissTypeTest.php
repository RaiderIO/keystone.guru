<?php

namespace Tests\Unit\App\Models\Spell;

use App\Logic\CombatLog\Guid\Guid;
use App\Models\Spell\SpellMissType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('Models')]
final class SpellMissTypeTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('missTypeGuidProvider')]
    public function tryFromGuid_givenMissTypeGuid_returnsMatchingMissType(string $guidString, SpellMissType $expected): void
    {
        // Arrange
        $guid = Guid::createFromGuidString($guidString);
        $this->assertNotNull($guid);

        // Act
        $missType = SpellMissType::tryFromGuid($guid);

        // Assert
        $this->assertSame($expected, $missType);
    }

    /**
     * @return array<string, array{string, SpellMissType}>
     */
    public static function missTypeGuidProvider(): array
    {
        return [
            'absorb'  => ['ABSORB', SpellMissType::Absorb],
            'block'   => ['BLOCK', SpellMissType::Block],
            'deflect' => ['DEFLECT', SpellMissType::Deflect],
            'dodge'   => ['DODGE', SpellMissType::Dodge],
            'evade'   => ['EVADE', SpellMissType::Evade],
            'immune'  => ['IMMUNE', SpellMissType::Immune],
            'miss'    => ['MISS', SpellMissType::Miss],
            'parry'   => ['PARRY', SpellMissType::Parry],
            'reflect' => ['REFLECT', SpellMissType::Reflect],
            'resist'  => ['RESIST', SpellMissType::Resist],
        ];
    }

    #[Test]
    public function tryFromGuid_givenEveryKnownMissTypeGuidClass_returnsTheMissTypeOfTheSameName(): void
    {
        // Arrange - a miss type GUID added to Guid without a case here would silently never be recorded
        foreach (Guid::GUID_MISS_TYPES as $guidString => $guidClass) {
            $guid = Guid::createFromGuidString($guidString);
            $this->assertInstanceOf($guidClass, $guid);

            // Act
            $missType = SpellMissType::tryFromGuid($guid);

            // Assert
            $this->assertNotNull($missType, sprintf('%s has no SpellMissType', $guidClass));
            $this->assertSame(strtolower($guidString), $missType->slug());
        }
    }

    #[Test]
    public function tryFromGuid_givenNonMissTypeGuid_returnsNull(): void
    {
        // Arrange
        $guid = Guid::createFromGuidString('Player-1084-0A5F4542');
        $this->assertNotNull($guid);

        // Act
        $missType = SpellMissType::tryFromGuid($guid);

        // Assert
        $this->assertNull($missType);
    }
}
