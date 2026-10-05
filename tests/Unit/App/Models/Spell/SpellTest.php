<?php

namespace Tests\Unit\App\Models\Spell;

use App\Models\Spell\Spell;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * A raw, unprefixed or otherwise unrecognised `dispel_type` value must never
 * be surfaced verbatim in the tooltip, and a genuinely informative one must render translated.
 */
#[Group('Models')]
final class SpellTest extends PublicTestCase
{
    private function makeSpell(string $dispelType): Spell
    {
        return new Spell([
            'description_format' => 'Some description',
            'dispel_type'        => $dispelType,
            'schools_mask'       => 0,
            'cast_time'          => 0,
            'duration'           => 0,
        ]);
    }

    #[Test]
    public function getTooltipDataAttribute_givenPrefixedInformativeDispelType_rendersTranslatedDispelType(): void
    {
        // Arrange
        $spell = $this->makeSpell('spelldispeltype.magic');

        // Act
        $tooltipData = $spell->tooltip_data;

        // Assert
        $this->assertSame('Magic', $tooltipData['dispelType']);
    }

    #[Test]
    public function getTranslatedName_givenLocaleWithTranslation_returnsThatTranslation(): void
    {
        // Arrange
        app('translator')->addLines(['spells.999999001' => 'English Name'], 'en_US');
        app('translator')->addLines(['spells.999999001' => 'Deutscher Name'], 'de_DE_ai');
        $spell = new Spell(['name' => 'spells.999999001']);

        // Act
        $translatedName = $spell->getTranslatedName('de_DE_ai');

        // Assert
        $this->assertSame('Deutscher Name', $translatedName);
    }

    #[Test]
    public function getTranslatedName_givenLocaleWithEmptyTranslation_returnsEnglishName(): void
    {
        // Arrange
        app('translator')->addLines(['spells.999999002' => 'English Name'], 'en_US');
        app('translator')->addLines(['spells.999999002' => ''], 'de_DE_ai');
        $spell = new Spell(['name' => 'spells.999999002']);

        // Act
        $translatedName = $spell->getTranslatedName('de_DE_ai');

        // Assert
        $this->assertSame('English Name', $translatedName);
    }

    #[Test]
    public function getTranslatedName_givenNoLocale_usesApplicationLocale(): void
    {
        // Arrange
        app('translator')->addLines(['spells.999999003' => 'English Name'], 'en_US');
        app('translator')->addLines(['spells.999999003' => 'Nom français'], 'fr_FR_ai');
        $spell = new Spell(['name' => 'spells.999999003']);
        app()->setLocale('fr_FR_ai');

        // Act
        $translatedName = $spell->getTranslatedName();

        // Assert
        $this->assertSame('Nom français', $translatedName);
    }

    #[Test]
    public function getTranslatedName_givenNameThatIsNoTranslationKey_returnsNameItself(): void
    {
        // Arrange
        $spell = new Spell(['name' => 'Some Untranslated Spell']);

        // Act
        $translatedName = $spell->getTranslatedName('de_DE_ai');

        // Assert
        $this->assertSame('Some Untranslated Spell', $translatedName);
    }

    #[Test]
    public function getTooltipData_givenLocaleWithEmptyNameTranslation_returnsEnglishName(): void
    {
        // Arrange
        app('translator')->addLines(['spells.999999004' => 'English Name'], 'en_US');
        app('translator')->addLines(['spells.999999004' => ''], 'de_DE_ai');
        $spell       = $this->makeSpell('spelldispeltype.magic');
        $spell->name = 'spells.999999004';

        // Act
        $tooltipData = $spell->getTooltipData('de_DE_ai');

        // Assert
        $this->assertSame('English Name', $tooltipData['name']);
    }

    #[Test]
    public function getRouteKey_givenLocaleWithEmptyNameTranslation_slugsEnglishName(): void
    {
        // Arrange
        app('translator')->addLines(['spells.999999005' => 'English Name'], 'en_US');
        app('translator')->addLines(['spells.999999005' => ''], 'de_DE_ai');
        $spell = new Spell(['id' => 999999005, 'name' => 'spells.999999005']);
        app()->setLocale('de_DE_ai');

        // Act
        $routeKey = $spell->getRouteKey();

        // Assert
        $this->assertSame('999999005-english-name', $routeKey);
    }

    #[Test]
    #[DataProvider('uninformativeOrDriftedDispelTypeProvider')]
    public function getTooltipDataAttribute_givenUninformativeOrDriftedDispelType_omitsDispelTypeRow(string $dispelType): void
    {
        // Arrange
        $spell = $this->makeSpell($dispelType);

        // Act
        $tooltipData = $spell->tooltip_data;

        // Assert
        $this->assertArrayNotHasKey('dispelType', $tooltipData);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function uninformativeOrDriftedDispelTypeProvider(): array
    {
        return [
            'prefixed none'      => ['spelldispeltype.none'],
            'prefixed n/a (Sap)' => ['spelldispeltype.n_a'],
            'prefixed unknown'   => ['spelldispeltype.unknown'],
            'empty string'       => [''],
            // Unprefixed legacy/drifted values - must be suppressed, never printed raw.
            'unprefixed magic'       => ['magic'],
            'unprefixed n_a'         => ['n_a'],
            'unprefixed unknown'     => ['unknown'],
            'not a real dispel type' => ['spelldispeltype.does_not_exist'],
        ];
    }
}
