<?php

namespace Tests\Unit\App\Models\CombatLog;

use App\Models\CombatLog\SpellProperty;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellCounter;
use App\Models\Spell\SpellImmunity;
use App\Models\Spell\SpellMissType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SpellProperty')]
final class SpellPropertyTest extends PublicTestCase
{
    #[Test]
    public function columnAndMaskBit_givenEveryProperty_resolveToTheColumnAndBitTheSpellEnumsDeclare(): void
    {
        // Arrange - Spell::recordCombatLogProperty() builds its conditional UPDATE from these two, so a property
        // that resolves to the wrong column or bit would silently record the wrong fact
        $expectedColumns = [
            'aura'                     => ['aura'],
            'debuff'                   => ['debuff'],
            'miss_types_mask'          => SpellMissType::slugsByBit(),
            'counters_mask'            => SpellCounter::slugsByBit(),
            'bypasses_immunities_mask' => SpellImmunity::slugsByBit(),
        ];

        foreach (SpellProperty::cases() as $property) {
            // Act
            $column  = $property->column();
            $maskBit = $property->maskBit();

            // Assert
            $this->assertArrayHasKey($column, $expectedColumns, sprintf('%s resolved to an unknown column', $property->value));

            if (in_array($property, [SpellProperty::Aura, SpellProperty::Debuff], true)) {
                $this->assertNull($maskBit, sprintf('%s is stored as a boolean and must have no mask bit', $property->value));

                continue;
            }

            $this->assertNotNull($maskBit, sprintf('%s is stored in a mask and must have a bit', $property->value));
            $this->assertArrayHasKey($maskBit, $expectedColumns[$column], sprintf('%s resolved to a bit that %s does not declare', $property->value, $column));
        }
    }

    #[Test]
    public function maskBit_givenEveryMaskProperty_isUniqueWithinItsColumn(): void
    {
        // Arrange
        $seen = [];

        foreach (SpellProperty::cases() as $property) {
            $maskBit = $property->maskBit();
            if ($maskBit === null) {
                continue;
            }

            // Act
            $key = sprintf('%s-%d', $property->column(), $maskBit);

            // Assert - two properties sharing a bit would make one of them impossible to record or remove
            $this->assertArrayNotHasKey($key, $seen, sprintf('%s shares its bit with %s', $property->value, $seen[$key] ?? ''));
            $seen[$key] = $property->value;
        }
    }

    #[Test]
    #[DataProvider('columnAndMaskBitProvider')]
    public function columnAndMaskBit_givenAProperty_returnTheColumnAndBitItIsStoredIn(SpellProperty $property, string $expectedColumn, ?int $expectedMaskBit): void
    {
        // Act
        $column  = $property->column();
        $maskBit = $property->maskBit();

        // Assert
        $this->assertSame($expectedColumn, $column);
        $this->assertSame($expectedMaskBit, $maskBit);
    }

    /**
     * @return array<string, array{SpellProperty, string, int|null}>
     */
    public static function columnAndMaskBitProvider(): array
    {
        return [
            'Aura'                         => [SpellProperty::Aura, 'aura', null],
            'Debuff'                       => [SpellProperty::Debuff, 'debuff', null],
            'MissAbsorb'                   => [SpellProperty::MissAbsorb, 'miss_types_mask', 1],
            'MissBlock'                    => [SpellProperty::MissBlock, 'miss_types_mask', 2],
            'MissDeflect'                  => [SpellProperty::MissDeflect, 'miss_types_mask', 4],
            'MissDodge'                    => [SpellProperty::MissDodge, 'miss_types_mask', 8],
            'MissEvade'                    => [SpellProperty::MissEvade, 'miss_types_mask', 16],
            'MissImmune'                   => [SpellProperty::MissImmune, 'miss_types_mask', 32],
            'MissMiss'                     => [SpellProperty::MissMiss, 'miss_types_mask', 64],
            'MissParry'                    => [SpellProperty::MissParry, 'miss_types_mask', 128],
            'MissReflect'                  => [SpellProperty::MissReflect, 'miss_types_mask', 256],
            'MissResist'                   => [SpellProperty::MissResist, 'miss_types_mask', 512],
            'MissInterrupt'                => [SpellProperty::MissInterrupt, 'miss_types_mask', 1024],
            'CounterVanish'                => [SpellProperty::CounterVanish, 'counters_mask', 1],
            'CounterShadowmeld'            => [SpellProperty::CounterShadowmeld, 'counters_mask', 2],
            'CounterFeignDeath'            => [SpellProperty::CounterFeignDeath, 'counters_mask', 4],
            'CounterInvisibility'          => [SpellProperty::CounterInvisibility, 'counters_mask', 8],
            'CounterCloakOfShadows'        => [SpellProperty::CounterCloakOfShadows, 'counters_mask', 16],
            'BypassDivineShield'           => [SpellProperty::BypassDivineShield, 'bypasses_immunities_mask', 1],
            'BypassIceBlock'               => [SpellProperty::BypassIceBlock, 'bypasses_immunities_mask', 2],
            'BypassAspectOfTheTurtle'      => [SpellProperty::BypassAspectOfTheTurtle, 'bypasses_immunities_mask', 4],
            'BypassBlessingOfProtection'   => [SpellProperty::BypassBlessingOfProtection, 'bypasses_immunities_mask', 8],
            'BypassBlessingOfSpellwarding' => [SpellProperty::BypassBlessingOfSpellwarding, 'bypasses_immunities_mask', 16],
            'BypassAntiMagicShell'         => [SpellProperty::BypassAntiMagicShell, 'bypasses_immunities_mask', 32],
        ];
    }

    #[Test]
    public function fromMissTypeBit_givenAMissTypeBit_returnsTheMissPropertyOfThatType(): void
    {
        // Arrange
        $bit = SpellMissType::Interrupt->value;

        // Act
        $property = SpellProperty::fromMissTypeBit($bit);

        // Assert
        $this->assertSame(SpellProperty::MissInterrupt, $property);
    }

    #[Test]
    public function translationKey_givenABooleanProperty_returnsNull(): void
    {
        // Arrange
        $property = SpellProperty::Debuff;

        // Act
        $translationKey = $property->translationKey();

        // Assert
        $this->assertNull($translationKey);
    }

    #[Test]
    #[DataProvider('translationKeyProvider')]
    public function translationKey_givenAMaskProperty_returnsTheKeyOfTheEnumCaseItStandsFor(SpellProperty $property, string $expected): void
    {
        // Act
        $translationKey = $property->translationKey();

        // Assert
        $this->assertSame($expected, $translationKey);
    }

    /**
     * @return array<string, array{SpellProperty, string}>
     */
    public static function translationKeyProvider(): array
    {
        return [
            'miss type' => [SpellProperty::MissParry, 'spellmisstypes.parry'],
            'counter'   => [SpellProperty::CounterFeignDeath, 'spellcounters.feign_death'],
            'immunity'  => [SpellProperty::BypassIceBlock, 'spellimmunities.ice_block'],
        ];
    }
}
