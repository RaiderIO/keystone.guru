<?php

namespace Tests\Feature\App\Logic\MapContext;

use App\Models\Faction;
use App\Service\MapContext\MapContextServiceInterface;
use Illuminate\Support\Facades\Cache;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('MapContext')]
final class MapContextStaticDataTest extends PublicTestCase
{
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
        $enSpells = app(MapContextServiceInterface::class)->createMapContextStaticData('en_US')->toArray()['static']['selectableSpells'];
        $deSpells = app(MapContextServiceInterface::class)->createMapContextStaticData('de_DE')->toArray()['static']['selectableSpells'];

        $enNamesByKey = $enSpells->pluck('name', 'id');
        $deNamesByKey = $deSpells->pluck('name', 'id');

        // Assert
        $this->assertNotEmpty($enNamesByKey);
        $this->assertSame($enNamesByKey->keys()->all(), $deNamesByKey->keys()->all());
        $this->assertNotEquals($enNamesByKey->all(), $deNamesByKey->all());
    }
}
