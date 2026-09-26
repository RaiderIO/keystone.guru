<?php

namespace Tests\Feature\App\Logic\MapContext;

use App\Models\Faction;
use App\Models\Spell\Spell;
use App\Models\Spell\SpellDescriptionTranslation;
use App\Models\Spell\SpellSchool;
use App\Service\MapContext\MapContextServiceInterface;
use App\Service\WagoTools\GameLocale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MapContext')]
final class MapContextStaticDataTest extends PublicTestCase
{
    private const int SPELL_ID = 999999802;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // RemembersToFile writes to the `tmp_file` file store, which survives between test runs -
        // without this the assertions below could run against a payload built by older code.
        Cache::store('tmp_file')->flush();
    }

    #[Test]
    public function toArray_givenFactions_serializesIconUrlWithoutIconFile(): void
    {
        // Arrange
        $mapContextStaticData = app(MapContextServiceInterface::class)->createMapContextStaticData('en_US');

        // Act - round-trip through json, since that is exactly what JavascriptController and
        // make:mapcontextstatic hand to the front-end.
        $factions = json_decode(json_encode($mapContextStaticData->toArray()['static']['factions']), true);

        // Assert - FactionDisplayControls reads faction.icon_url; it used to read
        // faction.iconfile.icon_url, which no longer exists.
        $this->assertNotEmpty($factions);

        foreach ($factions as $faction) {
            $this->assertArrayHasKey('icon_url', $faction);
            $this->assertArrayNotHasKey('iconfile', $faction);
            $this->assertSame(ksgAssetImage(sprintf('factions/%s.png', $faction['key'])), $faction['icon_url']);
        }
    }

    #[Test]
    public function toArray_givenFactions_excludesUnspecifiedFaction(): void
    {
        // Arrange
        $mapContextStaticData = app(MapContextServiceInterface::class)->createMapContextStaticData('en_US');

        // Act
        $factions = json_decode(json_encode($mapContextStaticData->toArray()['static']['factions']), true);

        // Assert - FactionDisplayControls only ever offers a Horde/Alliance toggle; the Unspecified
        // faction should never be part of this payload.
        $factionKeys = array_column($factions, 'key');
        $this->assertNotContains(Faction::FACTION_UNSPECIFIED, $factionKeys);
    }

    #[Test]
    public function toArray_givenTwoLocales_returnsPayloadsWithDistinctTranslatedSpellNames(): void
    {
        // Arrange - the static cache key used to hardcode the '%s' placeholder instead of
        // interpolating the locale, so every locale shared a single cache entry and every locale
        // but the first one to run ended up serving that first locale's translations.
        // Spell 465 (Devotion Aura) is seeded with distinct en_US/de_DE translations.
        $devotionAuraSpellId = 465;

        // Act
        $enNamesByKey = $this->getSelectableSpells('en_US')->pluck('name', 'id');
        $deNamesByKey = $this->getSelectableSpells('de_DE')->pluck('name', 'id');

        // Assert
        $this->assertNotEmpty($enNamesByKey);
        $this->assertEqualsCanonicalizing($enNamesByKey->keys()->all(), $deNamesByKey->keys()->all());
        $this->assertSame('Devotion Aura', $enNamesByKey->get($devotionAuraSpellId));
        $this->assertSame('Aura der Hingabe', $deNamesByKey->get($devotionAuraSpellId));
    }

    #[Test]
    public function toArray_givenALocaleOtherThanTheApplicationLocale_carriesThatLocalesSpellDescriptions(): void
    {
        // Arrange - make:mapcontextstatic writes one file per locale from a single process, whose own
        // locale is whatever the CLI started in. A tooltip's description resolves through that locale,
        // not through the one the payload is being built for, so the two have to be brought together.
        app()->setLocale('en_US');

        /** @var SpellDescriptionTranslation $translation */
        $translation = SpellDescriptionTranslation::query()
            ->where('locale', GameLocale::German->value)
            ->whereIn('spell_id', Spell::query()->where('selectable', true)->select('id'))
            ->firstOrFail();

        // Act
        $spell = $this->getSelectableSpells('de_DE')->firstWhere('id', $translation->spell_id);

        // Assert
        $this->assertNotNull($spell);
        $this->assertSame($translation->description_format, $spell['tooltip_data']['format']);
        $this->assertSame('en_US', app()->getLocale(), 'The application locale is left alone');
    }

    #[Test]
    public function toArray_givenEnglish_carriesTheEnglishSpellDescription(): void
    {
        // Arrange
        $spell = null;

        try {
            $spell = $this->createSelectableSpell();
            $this->createTranslation(GameLocale::French, 'Frappe le sol pendant 8 s.');

            // Act
            $tooltipData = $this->getSelectableSpells('en_US')->firstWhere('id', self::SPELL_ID)['tooltip_data'];

            // Assert
            $this->assertSame('Slams the ground for 8 sec.', $tooltipData['format']);
            $this->assertSame(__('spellschools.physical', [], 'en_US'), $tooltipData['schools']);
        } finally {
            $this->deleteTranslations();
            $spell?->delete();
        }
    }

    #[Test]
    public function toArray_givenALocaleOtherThanTheApplicationLocale_translatesTheTooltipIntoThatLocale(): void
    {
        // Arrange
        app()->setLocale('en_US');
        $spell = null;

        try {
            $spell = $this->createSelectableSpell();
            $this->createTranslation(GameLocale::French, 'Frappe le sol pendant 8 s.');

            // Act
            $tooltipData = $this->getSelectableSpells('fr_FR_ai')->firstWhere('id', self::SPELL_ID)['tooltip_data'];

            // Assert
            $this->assertSame('Frappe le sol pendant 8 s.', $tooltipData['format']);
            $this->assertSame(__('spellschools.physical', [], 'fr_FR_ai'), $tooltipData['schools']);
            $this->assertNotSame(__('spellschools.physical', [], 'en_US'), $tooltipData['schools']);
            $this->assertSame('en_US', app()->getLocale());
        } finally {
            $this->deleteTranslations();
            $spell?->delete();
        }
    }

    #[Test]
    public function toArray_givenALocaleWithoutATranslationForTheSpell_fallsBackToTheEnglishDescription(): void
    {
        // Arrange
        app()->setLocale('en_US');
        $spell = null;

        try {
            $spell = $this->createSelectableSpell();
            $this->createTranslation(GameLocale::French, 'Frappe le sol pendant 8 s.');

            // Act
            $tooltipData = $this->getSelectableSpells('de_DE_ai')->firstWhere('id', self::SPELL_ID)['tooltip_data'];

            // Assert
            $this->assertSame('Slams the ground for 8 sec.', $tooltipData['format']);
        } finally {
            $this->deleteTranslations();
            $spell?->delete();
        }
    }

    #[Test]
    public function toArray_givenANonEnglishLocale_doesNotSerializeTheTranslationRelations(): void
    {
        // Arrange
        $spell = null;

        try {
            $spell = $this->createSelectableSpell();
            $this->createTranslation(GameLocale::French, 'Frappe le sol pendant 8 s.');

            // Act
            $serialized = $this->getSelectableSpells('fr_FR_ai')->firstWhere('id', self::SPELL_ID);

            // Assert
            $this->assertArrayNotHasKey('description_translations', $serialized);
            $this->assertArrayNotHasKey('description_translation', $serialized);
        } finally {
            $this->deleteTranslations();
            $spell?->delete();
        }
    }

    private function createSelectableSpell(): Spell
    {
        return Spell::create([
            'id'                 => self::SPELL_ID,
            'game_version_id'    => 1,
            'dispel_type'        => 'spelldispeltype.none',
            'icon_name'          => 'inv_misc_questionmark',
            'name'               => 'spells.test',
            'schools_mask'       => SpellSchool::Physical->value,
            'selectable'         => true,
            'description_format' => 'Slams the ground for 8 sec.',
        ]);
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

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function getSelectableSpells(string $locale): Collection
    {
        /** @var array<int, array<string, mixed>> $selectableSpells */
        $selectableSpells = app(MapContextServiceInterface::class)
            ->createMapContextStaticData($locale)
            ->toArray()['static']['selectableSpells'];

        return collect($selectableSpells);
    }
}
