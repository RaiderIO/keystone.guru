<?php

namespace Tests\Unit\App\Models\Spell;

use App\Models\Spell\SpellCategory;
use App\Models\Spell\SpellCooldownGroup;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * Both lists feed `Rule::in` and the admin dropdowns, and are stored as translation-key suffixes, so their values
 * and order must never move.
 */
#[Group('Models')]
final class SpellCategoryAndCooldownGroupTest extends PublicTestCase
{
    #[Test]
    public function values_givenSpellCategory_returnsTheStoredValuesInOrder(): void
    {
        // Arrange
        $expected = [
            'general',
            'warrior',
            'hunter',
            'death_knight',
            'mage',
            'priest',
            'monk',
            'rogue',
            'warlock',
            'shaman',
            'paladin',
            'druid',
            'demon_hunter',
            'evoker',
            'unknown',
        ];

        // Act
        $values = SpellCategory::values();

        // Assert
        $this->assertSame($expected, $values);
    }

    #[Test]
    public function values_givenSpellCooldownGroup_returnsTheStoredValuesInOrder(): void
    {
        // Arrange
        $expected = [
            'all',
            'cd_external',
            'cd_group',
            'cd_personal',
            'dr_external',
            'dr_group',
            'dr_personal',
            'group_dr',
            'group_heal_dps',
            'immunity',
            'movement',
            'personal',
            'personal_cd',
            'utility',
            'unknown',
        ];

        // Act
        $values = SpellCooldownGroup::values();

        // Assert
        $this->assertSame($expected, $values);
    }
}
