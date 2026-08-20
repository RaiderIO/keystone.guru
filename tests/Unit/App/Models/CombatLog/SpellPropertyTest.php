<?php

namespace Tests\Unit\App\Models\CombatLog;

use App\Models\CombatLog\SpellProperty;
use App\Models\Spell\Spell;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SpellProperty')]
final class SpellPropertyTest extends PublicTestCase
{
    #[Test]
    public function columnAndMaskBit_givenEveryProperty_resolveToTheColumnAndBitTheSpellConstantsDeclare(): void
    {
        // Arrange - Spell::recordCombatLogProperty() builds its conditional UPDATE from these two, so a property
        // that resolves to the wrong column or bit would silently record the wrong fact
        $expectedColumns = [
            'aura'                     => ['aura'],
            'debuff'                   => ['debuff'],
            'miss_types_mask'          => Spell::ALL_MISS_TYPES,
            'counters_mask'            => Spell::ALL_COUNTERS,
            'bypasses_immunities_mask' => Spell::ALL_IMMUNITIES,
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
}
