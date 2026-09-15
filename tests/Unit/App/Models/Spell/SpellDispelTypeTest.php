<?php

namespace Tests\Unit\App\Models\Spell;

use App\Models\Spell\SpellDispelType;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * `spells`.`dispel_type` stores the prefixed translation key and the map context ships the bare values, so both
 * lists must never move.
 */
#[Group('Models')]
final class SpellDispelTypeTest extends PublicTestCase
{
    #[Test]
    public function values_givenAllCases_returnsTheBareValuesInOrder(): void
    {
        // Arrange
        $expected = ['magic', 'disease', 'poison', 'curse', 'enrage', 'none', 'n_a', 'unknown'];

        // Act
        $values = SpellDispelType::values();

        // Assert
        $this->assertSame($expected, $values);
    }

    #[Test]
    public function translationKeys_givenAllCases_returnsTheStoredFormInOrder(): void
    {
        // Arrange
        $expected = [
            'spelldispeltype.magic',
            'spelldispeltype.disease',
            'spelldispeltype.poison',
            'spelldispeltype.curse',
            'spelldispeltype.enrage',
            'spelldispeltype.none',
            'spelldispeltype.n_a',
            'spelldispeltype.unknown',
        ];

        // Act
        $translationKeys = SpellDispelType::translationKeys();

        // Assert
        $this->assertSame($expected, $translationKeys);
    }

    #[Test]
    public function translationKey_givenNotAvailable_returnsPrefixedBackingValue(): void
    {
        // Arrange
        $dispelType = SpellDispelType::NotAvailable;

        // Act
        $translationKey = $dispelType->translationKey();

        // Assert
        $this->assertSame('spelldispeltype.n_a', $translationKey);
    }

    #[Test]
    public function translationKey_givenEveryCase_resolvesToAnEnglishTranslation(): void
    {
        foreach (SpellDispelType::cases() as $dispelType) {
            // Arrange
            $translationKey = $dispelType->translationKey();

            // Act
            $translated = __($translationKey, [], 'en_US');

            // Assert
            $this->assertNotSame($translationKey, $translated, sprintf('%s has no en_US translation', $translationKey));
        }
    }
}
