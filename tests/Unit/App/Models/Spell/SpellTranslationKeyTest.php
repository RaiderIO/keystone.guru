<?php

namespace Tests\Unit\App\Models\Spell;

use App\Models\Spell\SpellCategory;
use App\Models\Spell\SpellCooldownGroup;
use App\Models\Spell\SpellCounter;
use App\Models\Spell\SpellDispelType;
use App\Models\Spell\SpellImmunity;
use App\Models\Spell\SpellMechanic;
use App\Models\Spell\SpellMissType;
use App\Models\Spell\SpellSchool;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The keys the spell enums build are what `lang/en_US/spell*.php` is keyed by and, for category, cooldown group,
 * mechanic and dispel type, what the `spells` columns store. Both break silently on a changed key - a missing
 * translation renders as the raw key, and a stored value stops matching - so every key is pinned here.
 */
#[Group('Models')]
final class SpellTranslationKeyTest extends PublicTestCase
{
    #[Test]
    #[DataProvider('translationKeyProvider')]
    public function translationKey_givenACase_returnsTheKeyTheLangFilesAreKeyedBy(SpellCategory|SpellCooldownGroup|SpellCounter|SpellDispelType|SpellImmunity|SpellMechanic|SpellMissType|SpellSchool $case, string $expected): void
    {
        // Arrange - nothing to arrange, the key is derived from the case

        // Act
        $translationKey = $case->translationKey();

        // Assert
        $this->assertSame($expected, $translationKey);
    }

    /**
     * @return array<string, array{SpellCategory|SpellCooldownGroup|SpellCounter|SpellDispelType|SpellImmunity|SpellMechanic|SpellMissType|SpellSchool, string}>
     */
    public static function translationKeyProvider(): array
    {
        return [
            'category'       => [SpellCategory::DeathKnight, 'spellcategory.death_knight'],
            'cooldown group' => [SpellCooldownGroup::GroupHealDps, 'spellcooldowngroup.group_heal_dps'],
            'dispel type'    => [SpellDispelType::NotAvailable, 'spelldispeltype.n_a'],
            'mechanic'       => [SpellMechanic::Incapacitated, 'spellmechanic.incapacitated'],
            'school'         => [SpellSchool::Arcane, 'spellschools.arcane'],
            'counter'        => [SpellCounter::CloakOfShadows, 'spellcounters.cloak_of_shadows'],
            'immunity'       => [SpellImmunity::AntiMagicShell, 'spellimmunities.anti_magic_shell'],
            'miss type'      => [SpellMissType::Interrupt, 'spellmisstypes.interrupt'],
        ];
    }

    /**
     * @param class-string<SpellCategory|SpellCooldownGroup|SpellCounter|SpellDispelType|SpellImmunity|SpellMechanic|SpellMissType|SpellSchool> $enumClass
     */
    #[Test]
    #[DataProvider('enumClassProvider')]
    public function translationKey_givenEveryCase_resolvesToAnEnglishTranslation(string $enumClass): void
    {
        foreach ($enumClass::cases() as $case) {
            // Arrange
            $translationKey = $case->translationKey();

            // Act
            $translated = __($translationKey, [], 'en_US');

            // Assert
            $this->assertNotSame($translationKey, $translated, sprintf('%s has no en_US translation', $translationKey));
        }
    }

    /**
     * @return array<string, array{class-string<SpellCategory|SpellCooldownGroup|SpellCounter|SpellDispelType|SpellImmunity|SpellMechanic|SpellMissType|SpellSchool>}>
     */
    public static function enumClassProvider(): array
    {
        return [
            'category'       => [SpellCategory::class],
            'cooldown group' => [SpellCooldownGroup::class],
            'dispel type'    => [SpellDispelType::class],
            'mechanic'       => [SpellMechanic::class],
            'school'         => [SpellSchool::class],
            'counter'        => [SpellCounter::class],
            'immunity'       => [SpellImmunity::class],
            'miss type'      => [SpellMissType::class],
        ];
    }

    #[Test]
    public function translationKeyFor_givenASlugThatIsNotACase_stillReturnsAPrefixedKey(): void
    {
        // Arrange
        $categorySlug = 'not_a_class';

        // Act
        $translationKey = SpellCategory::translationKeyFor($categorySlug);

        // Assert
        $this->assertSame('spellcategory.not_a_class', $translationKey);
    }

    #[Test]
    public function maskToTranslatedString_givenAMaskWithTwoBits_returnsBothNamesInDeclarationOrder(): void
    {
        // Arrange
        $mask = SpellSchool::Shadow->value | SpellSchool::Fire->value;

        // Act
        $readable = SpellSchool::maskToTranslatedString($mask);

        // Assert
        $this->assertSame(sprintf('%s, %s', __('spellschools.fire'), __('spellschools.shadow')), $readable);
    }

    #[Test]
    public function maskToTranslatedString_givenALocaleOtherThanTheApplicationLocale_translatesIntoThatLocale(): void
    {
        // Arrange
        app()->setLocale('en_US');
        $mask = SpellSchool::Physical->value;

        // Act
        $readable = SpellSchool::maskToTranslatedString($mask, 'fr_FR_ai');

        // Assert
        $this->assertSame(__('spellschools.physical', [], 'fr_FR_ai'), $readable);
        $this->assertNotSame(__('spellschools.physical', [], 'en_US'), $readable);
    }

    #[Test]
    public function maskToTranslatedString_givenAnEmptyMask_returnsAnEmptyString(): void
    {
        // Arrange
        $mask = 0;

        // Act
        $readable = SpellMissType::maskToTranslatedString($mask);

        // Assert
        $this->assertSame('', $readable);
    }
}
