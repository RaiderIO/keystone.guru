<?php

namespace Tests\Feature\View;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\Dungeon\DungeonServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

/**
 * The header's dungeon context: retail keeps its row of image tiles, every seasonless game version lists all of
 * its dungeons and raids as grouped chips - in the desktop strip and the mobile dropdown alike - and neither
 * offers a "More" way out any longer.
 */
#[Group('View')]
#[Group('DungeonContext')]
final class DungeonContextStripTest extends PublicTestCase
{
    private const string DESKTOP_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';

    #[Test]
    public function home_givenAGuestOnRetail_rendersImageTilesWithoutChips(): void
    {
        // Arrange
        $this->actingAsGuest();

        // Act
        $html = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/')->assertOk()->getContent();

        // Assert
        $this->assertStringContainsString('list_dungeon', $html);
        $this->assertStringNotContainsString('dungeon_strip', $html);
    }

    #[Test]
    public function home_givenAGuestOnClassicEra_rendersEveryDungeonAsAChipAndNoMoreLink(): void
    {
        // Arrange
        $this->actingAsGuest();
        $dungeons                = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_CLASSIC_ERA;

        $this->assertGreaterThan(8, $dungeons->count(), 'Need more Classic Era dungeons than the old strip held');

        try {
            // Act
            $html = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->get('/')->assertOk()->getContent();

            // Assert
            $this->assertSame($dungeons->count(), preg_match_all('/class="dungeon_strip_chip[ "]/', $html));
            $this->assertStringNotContainsString('list_dungeon', $html);
            $this->assertStringNotContainsString(
                route('dungeon.explore.gameversion.select', ['gameVersion' => GameVersion::GAME_VERSION_CLASSIC_ERA]),
                $html,
            );
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    public function explore_givenAGuestOnMists_linksEveryChipToTheExplorePageWithoutAMoreLink(): void
    {
        // Arrange
        $this->actingAsGuest();
        $mop      = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_MOP);
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_MOP);
        /** @var Dungeon|null $dungeon */
        $dungeon                 = $dungeons->first(static fn(Dungeon $dungeon) => $dungeon->getCurrentMappingVersionForGameVersion($mop) !== null);
        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_MOP;

        $this->assertNotNull($dungeon, 'Need a seeded MoP dungeon with a MoP mapping version');

        try {
            // Act
            $html = $this->withHeader('User-Agent', self::DESKTOP_USER_AGENT)->followingRedirects()->get(route('dungeon.explore.gameversion.view', [
                'gameVersion' => $mop,
                'dungeon'     => $dungeon,
            ]))->assertOk()->getContent();

            // Assert
            $this->assertStringContainsString('id="map_header"', $html, 'Expected the map, not the selection page');
            $strip = $this->getStripHtml($html);
            foreach ($dungeons as $stripDungeon) {
                $this->assertStringContainsString(
                    sprintf('href="%s"', route('dungeon.explore.gameversion.view', ['gameVersion' => $mop, 'dungeon' => $stripDungeon])),
                    $strip,
                );
            }

            $this->assertStringNotContainsString(route('dungeon.explore.gameversion.select', ['gameVersion' => $mop]), $html);
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    #[Test]
    public function render_givenASeasonlessGameVersion_groupsTheChipsUnderTheirSelectorGroup(): void
    {
        // Arrange
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $raid     = $dungeons->first(static fn(Dungeon $dungeon) => $dungeon->raid);
        $dungeon  = $dungeons->first(static fn(Dungeon $dungeon) => !$dungeon->raid);

        $this->assertNotNull($raid, 'Need a seeded Classic Era raid');

        // Act
        $html = $this->renderList(GameVersion::GAME_VERSION_CLASSIC_ERA, $dungeons);

        // Assert
        $groups = $this->getChipLabelsByGroup($html);
        $this->assertSame(['dungeon', 'raid'], array_keys($groups));
        $this->assertContains(__($dungeon->name), $groups['dungeon']);
        $this->assertContains(__($raid->name), $groups['raid']);
        $this->assertNotContains(__($raid->name), $groups['dungeon']);
    }

    #[Test]
    public function render_givenASeasonlessGameVersion_namesEveryChipInItsLabelAndTitle(): void
    {
        // Arrange
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);
        /** @var Dungeon $dungeon */
        $dungeon = $dungeons->first();

        // Act
        $html = $this->renderList(GameVersion::GAME_VERSION_CLASSIC_ERA, $dungeons);

        // Assert
        $this->assertMatchesRegularExpression(
            sprintf(
                '/<a class="dungeon_strip_chip[^"]*"\s+href="[^"]*"\s+aria-label="%1$s" title="%1$s"[^>]*>%2$s<\/a>/',
                preg_quote(e(__($dungeon->name)), '/'),
                preg_quote(e(__($dungeon->abbreviation)), '/'),
            ),
            $html,
        );
    }

    #[Test]
    public function render_givenTheSelectedDungeon_marksOnlyItsChipCurrentAndNamesItInTheReadout(): void
    {
        // Arrange
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);
        /** @var Dungeon $selected */
        $selected = $dungeons->get(1);

        // Act
        $html = $this->renderList(GameVersion::GAME_VERSION_CLASSIC_ERA, $dungeons, $selected->key);

        // Assert
        $this->assertSame(1, substr_count($html, 'aria-current="true"'));
        $this->assertMatchesRegularExpression(
            sprintf('/<a class="dungeon_strip_chip border-accent"[^>]*title="%s"[^>]*aria-current="true"/', preg_quote(e(__($selected->name)), '/')),
            $html,
        );
        $this->assertMatchesRegularExpression(
            sprintf('/<span class="dungeon_strip_readout_name">%s<\/span>/', preg_quote(e(__($selected->name)), '/')),
            $html,
        );
    }

    #[Test]
    public function render_givenASelectedDungeonOutsideTheList_invitesAChoiceInTheReadout(): void
    {
        // Arrange - a guest's default dungeon on TBC Classic is a TBC dungeon, which TBC Classic does not list
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);

        // Act
        $html = $this->renderList(GameVersion::GAME_VERSION_CLASSIC_ERA, $dungeons, 'not_a_listed_dungeon');

        // Assert
        $this->assertStringNotContainsString('aria-current', $html);
        $this->assertMatchesRegularExpression(
            sprintf('/<span class="dungeon_strip_readout_name">%s<\/span>/', preg_quote(e(__('view_common.dungeon.list.choose_dungeon')), '/')),
            $html,
        );
    }

    #[Test]
    public function render_givenRetail_marksOnlyTheSelectedTileCurrent(): void
    {
        // Arrange
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_RETAIL);
        /** @var Dungeon $selected */
        $selected = $dungeons->last();

        // Act
        $html = $this->renderList(GameVersion::GAME_VERSION_RETAIL, $dungeons, $selected->key);

        // Assert
        $this->assertSame(1, substr_count($html, 'aria-current="true"'));
        $this->assertMatchesRegularExpression(
            sprintf('/<a href="%s"\s+aria-current="true"\s*>/', preg_quote(sprintf('/link/%s', $selected->key), '/')),
            $html,
        );
    }

    #[Test]
    public function render_givenClassicEraOnMobile_listsEveryDungeonUnderGroupHeadersWithoutAMoreEntry(): void
    {
        // Arrange
        $dungeons                = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_CLASSIC_ERA;

        try {
            // Act
            $html = view('common.layout.header')->render();

            // Assert
            $this->assertSame(1, preg_match('/<li class="nav-item dropdown dungeon_context_nav".*?<\/li>/s', $html, $matches));
            $selector = $matches[0];
            $this->assertSame($dungeons->count(), substr_count($selector, 'class="dropdown-item'));
            $this->assertStringContainsString(e(__('view_common.dungeon.list.groups.dungeon')), $selector);
            $this->assertStringContainsString(e(__('view_common.dungeon.list.groups.raid')), $selector);
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    /**
     * @return Collection<int, Dungeon>
     */
    private function getDungeons(string $gameVersionKey): Collection
    {
        return app(DungeonServiceInterface::class)->getDungeonsForGameVersion(GameVersion::firstWhere('key', $gameVersionKey));
    }

    /**
     * @param Collection<int, Dungeon> $dungeons
     */
    private function renderList(string $gameVersionKey, Collection $dungeons, ?string $selected = null): string
    {
        return view('common.dungeon.list', [
            'gameVersion'     => GameVersion::firstWhere('key', $gameVersionKey),
            'dungeons'        => $dungeons,
            'useAbbreviation' => true,
            'selected'        => $selected,
            'links'           => $dungeons->mapWithKeys(static fn(Dungeon $dungeon) => [$dungeon->key => sprintf('/link/%s', $dungeon->key)]),
        ])->render();
    }

    /**
     * The full names of the chips per group, in render order.
     *
     * @return array<string, array<int, string>>
     */
    private function getChipLabelsByGroup(string $html): array
    {
        preg_match_all('/aria-labelledby="dungeon_strip_group_([a-z]+)".*?<\/div>\s*<\/div>/s', $html, $groups, PREG_SET_ORDER);

        $result = [];
        foreach ($groups as [$groupHtml, $group]) {
            preg_match_all('/class="dungeon_strip_chip[^"]*"\s+href="[^"]*"\s+aria-label="([^"]*)"/', $groupHtml, $labels);
            $result[$group] = array_map(static fn(string $label) => html_entity_decode($label, ENT_QUOTES), $labels[1]);
        }

        return $result;
    }

    private function getStripHtml(string $html): string
    {
        $this->assertSame(1, preg_match('/<div class="dungeon_strip">.*?<button type="button" class="dungeon_strip_all"/s', $html, $matches));

        return $matches[0];
    }
}
