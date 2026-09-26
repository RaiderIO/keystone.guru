<?php

namespace Tests\Feature\View\Common;

use App\Models\Spell\Spell;
use App\Models\Spell\SpellDescriptionTranslation;
use App\Service\WagoTools\GameLocale;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('SpellDescription')]
final class SpellLinkTest extends PublicTestCase
{
    private const int SPELL_ID = 999999801;

    #[Test]
    public function render_givenSpellWithDescription_returnsOurOwnTooltip(): void
    {
        // Arrange
        $spell = null;

        try {
            $spell = $this->createSpell('Slams the ground for 8 sec.');

            // Act
            $result = view('common.spell.link', ['spell' => $spell])->render();

            // Assert
            $this->assertStringContainsString('data-spell-tooltip', $result);
            $this->assertStringContainsString('Slams the ground for 8 sec.', $result);
            $this->assertStringNotContainsString('data-wowhead', $result);
        } finally {
            $spell?->delete();
        }
    }

    #[Test]
    public function render_givenSpellWithoutDescription_fallsBackToWowhead(): void
    {
        // Arrange
        $spell = null;

        try {
            $spell = $this->createSpell(null);

            // Act
            $result = view('common.spell.link', ['spell' => $spell])->render();

            // Assert
            $this->assertStringContainsString(sprintf('data-wowhead="spell=%d"', self::SPELL_ID), $result);
            $this->assertStringNotContainsString('data-spell-tooltip', $result);
        } finally {
            $spell?->delete();
        }
    }

    #[Test]
    public function render_givenDescriptionWithMarkup_escapesIt(): void
    {
        // Arrange - descriptions come from an external data source and must never render as HTML
        $spell = null;

        try {
            $spell = $this->createSpell('<script>alert(1)</script>');

            // Act
            $result = view('common.spell.link', ['spell' => $spell])->render();

            // Assert
            $this->assertStringNotContainsString('<script>', $result);
            $this->assertStringContainsString('&lt;script&gt;', $result);
        } finally {
            $spell?->delete();
        }
    }

    #[Test]
    public function render_givenALocaleTheClientPublishes_returnsThatLocalesDescription(): void
    {
        // Arrange - a French visitor reads the French client's own sentence, not the English one
        $spell = null;

        try {
            $spell = $this->createSpell('Slams the ground for 8 sec.');
            $this->createTranslation(GameLocale::French, 'Frappe le sol pendant 8 s.');

            app()->setLocale('fr_FR');

            // Act
            $result = view('common.spell.link', ['spell' => $spell->fresh()])->render();

            // Assert
            $this->assertStringContainsString('Frappe le sol pendant 8 s.', $result);
            $this->assertStringNotContainsString('Slams the ground', $result);
        } finally {
            $this->deleteTranslations();
            $spell?->delete();
        }
    }

    #[Test]
    public function render_givenALocaleTheClientPublishesButWeHaveNoDescriptionFor_fallsBackToEnglish(): void
    {
        // Arrange - half a translation is worse than none; English is what the rest of the site falls back to
        $spell = null;

        try {
            $spell = $this->createSpell('Slams the ground for 8 sec.');
            $this->createTranslation(GameLocale::French, 'Frappe le sol pendant 8 s.');

            app()->setLocale('de_DE');

            // Act
            $result = view('common.spell.link', ['spell' => $spell->fresh()])->render();

            // Assert
            $this->assertStringContainsString('Slams the ground for 8 sec.', $result);
        } finally {
            $this->deleteTranslations();
            $spell?->delete();
        }
    }

    #[Test]
    public function render_givenTheAiVariantOfALocale_readsTheSameClientData(): void
    {
        // Arrange - `fr_FR_ai` is the same language, so it reads the same client text
        $spell = null;

        try {
            $spell = $this->createSpell('Slams the ground for 8 sec.');
            $this->createTranslation(GameLocale::French, 'Frappe le sol pendant 8 s.');

            app()->setLocale('fr_FR_ai');

            // Act
            $result = view('common.spell.link', ['spell' => $spell->fresh()])->render();

            // Assert
            $this->assertStringContainsString('Frappe le sol pendant 8 s.', $result);
        } finally {
            $this->deleteTranslations();
            $spell?->delete();
        }
    }

    #[Test]
    public function getTooltipDataAttribute_givenASpellSelectedWithoutItsDescriptionColumns_isNullWithoutLoadingTheTranslation(): void
    {
        // Arrange - a kill zone's spells are selected as id + icon_name and serialized into every route
        // payload; there is no description in those columns to render, in any locale
        $spell = null;

        try {
            $spell = $this->createSpell('Slams the ground for 8 sec.');
            $this->createTranslation(GameLocale::French, 'Frappe le sol pendant 8 s.');

            app()->setLocale('fr_FR_ai');

            /** @var Spell $partial */
            $partial = Spell::query()->select(['id', 'icon_name'])->findOrFail(self::SPELL_ID);

            // Act
            $tooltipData = $partial->tooltip_data;

            // Assert - and no lazy load, which throws outside production
            $this->assertNull($tooltipData);
            $this->assertFalse($partial->relationLoaded('descriptionTranslation'));
        } finally {
            $this->deleteTranslations();
            $spell?->delete();
        }
    }

    private function createTranslation(GameLocale $locale, string $format): SpellDescriptionTranslation
    {
        return SpellDescriptionTranslation::create([
            'spell_id'           => self::SPELL_ID,
            'locale'             => $locale->value,
            'description_format' => $format,
        ]);
    }

    private function deleteTranslations(): void
    {
        SpellDescriptionTranslation::query()->where('spell_id', self::SPELL_ID)->delete();
    }

    private function createSpell(?string $description): Spell
    {
        return Spell::create([
            'id'                 => self::SPELL_ID,
            'game_version_id'    => 1,
            'dispel_type'        => 'spelldispeltype.none',
            'icon_name'          => 'inv_misc_questionmark',
            'name'               => 'spells.test',
            'schools_mask'       => 1,
            'description_format' => $description,
        ]);
    }
}
