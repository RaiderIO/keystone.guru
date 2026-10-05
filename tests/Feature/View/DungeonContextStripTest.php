<?php

namespace Tests\Feature\View;

use App\Http\View\Composers\HeaderComposer;
use App\Models\AffixGroup\AffixGroup;
use App\Models\Dungeon;
use App\Models\DungeonKey;
use App\Models\GameVersion\GameVersion;
use App\Models\PageViewCount;
use App\Service\Dungeon\DungeonServiceInterface;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
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
    public function render_givenASeasonlessGameVersion_marksEveryGroupWithItsSelectorGroupModifier(): void
    {
        // Arrange
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);

        // Act
        $html = $this->renderList(GameVersion::GAME_VERSION_CLASSIC_ERA, $dungeons);

        // Assert
        preg_match_all('/class="dungeon_strip_group dungeon_strip_group--([a-z]+)"[^>]*aria-labelledby="dungeon_strip_group_([a-z]+)"/', $html, $groups, PREG_SET_ORDER);
        $this->assertSame(
            [['dungeon', 'dungeon'], ['raid', 'raid']],
            array_map(static fn(array $match) => [$match[1], $match[2]], $groups),
        );
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
    public function render_givenASeasonlessGameVersion_ordersEveryGroupsChipsByTheirAbbreviation(): void
    {
        // Arrange
        $dungeons  = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $deadmines = __($dungeons->firstWhere('key', DungeonKey::DEADMINES->value)->abbreviation ?? '');
        $nameOrder = $dungeons->reject(static fn(Dungeon $dungeon) => $dungeon->raid)->map(static fn(Dungeon $dungeon) => __($dungeon->abbreviation))->values()->all();

        $this->assertNotSame('', $deadmines, 'Need the seeded Deadmines');
        $this->assertContains('ST', $nameOrder, 'Need the seeded Sunken Temple');
        $this->assertGreaterThan(array_search('ST', $nameOrder, true), array_search($deadmines, $nameOrder, true), 'Need "The Deadmines" after ST by full name');

        // Act
        $html = $this->renderList(GameVersion::GAME_VERSION_CLASSIC_ERA, $dungeons);

        // Assert
        $abbreviationsByGroup = $this->getChipAbbreviationsByGroup($html);
        $this->assertSame(['dungeon', 'raid'], array_keys($abbreviationsByGroup));
        foreach ($abbreviationsByGroup as $group => $abbreviations) {
            $expected = $abbreviations;
            usort($expected, static fn(string $a, string $b) => strnatcasecmp($a, $b));
            $this->assertSame($expected, $abbreviations, sprintf('The %s chips are not in abbreviation order', $group));
        }

        $this->assertLessThan(array_search('ST', $abbreviationsByGroup['dungeon'], true), array_search($deadmines, $abbreviationsByGroup['dungeon'], true));
    }

    #[Test]
    #[DataProvider('groupIconProvider')]
    public function render_givenASeasonlessGameVersion_marksEveryGroupLabelWithAnIconAndItsName(string $gameVersionKey, string $group, string $icon): void
    {
        // Arrange
        $dungeons = $this->getDungeons($gameVersionKey);

        // Act
        $html = $this->renderList($gameVersionKey, $dungeons);

        // Assert: the name labels the group, the icon stands in for it in compact mode
        $this->assertMatchesRegularExpression(
            sprintf(
                '/<span class="dungeon_strip_group_label" title="%1$s">\s*<i class="fas %2$s dungeon_strip_group_icon" aria-hidden="true"><\/i>\s*<span class="dungeon_strip_group_name" id="dungeon_strip_group_%3$s">%1$s<\/span>/',
                preg_quote(e(__(sprintf('view_common.dungeon.list.groups.%s', $group))), '/'),
                preg_quote($icon, '/'),
                $group,
            ),
            $html,
        );
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function groupIconProvider(): array
    {
        return [
            'dungeons' => [GameVersion::GAME_VERSION_CLASSIC_ERA, 'dungeon', 'fa-dungeon'],
            'raids'    => [GameVersion::GAME_VERSION_CLASSIC_ERA, 'raid', 'fa-dragon'],
            'world'    => [GameVersion::GAME_VERSION_FOREVER, 'world', 'fa-globe'],
        ];
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
    public function render_givenViewShares_fillsEveryChipToItsShareAndDescribesIt(): void
    {
        // Arrange
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);
        /** @var Dungeon $mostViewed */
        $mostViewed = $dungeons->get(1);
        /** @var Dungeon $halfViewed */
        $halfViewed = $dungeons->get(2);
        $viewShares = $dungeons->mapWithKeys(static fn(Dungeon $dungeon) => [$dungeon->id => 0.0])
            ->put($mostViewed->id, 1.0)
            ->put($halfViewed->id, 0.4567);

        // Act
        $html = $this->renderList(GameVersion::GAME_VERSION_CLASSIC_ERA, $dungeons, null, $viewShares);

        // Assert
        $this->assertSame($dungeons->count(), substr_count($html, 'dungeon_strip_chip--views'));
        $this->assertMatchesRegularExpression($this->getViewsChipPattern($mostViewed, __('view_common.dungeon.list.chips.most_viewed'), '100%', '1'), $html);
        $this->assertMatchesRegularExpression(
            $this->getViewsChipPattern($halfViewed, __('view_common.dungeon.list.chips.view_share', ['percent' => 46]), '45.7%', '0.4567'),
            $html,
        );
        $this->assertMatchesRegularExpression(
            $this->getViewsChipPattern($dungeons->get(3), __('view_common.dungeon.list.chips.not_viewed'), '0%', '0'),
            $html,
        );
    }

    #[Test]
    public function render_givenATinyOrNearlyTopShare_neverDescribesItAsZeroOrAHundredPercent(): void
    {
        // Arrange
        $dungeons   = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $viewShares = collect([$dungeons->get(1)->id => 1.0, $dungeons->get(2)->id => 0.001, $dungeons->get(3)->id => 0.999]);

        // Act
        $html = $this->renderList(GameVersion::GAME_VERSION_CLASSIC_ERA, $dungeons, null, $viewShares);

        // Assert
        $this->assertStringContainsString(e(__('view_common.dungeon.list.chips.view_share', ['percent' => 1])), $html);
        $this->assertStringContainsString(e(__('view_common.dungeon.list.chips.view_share', ['percent' => 99])), $html);
        $this->assertStringNotContainsString(e(__('view_common.dungeon.list.chips.view_share', ['percent' => 0])), $html);
        $this->assertStringNotContainsString(e(__('view_common.dungeon.list.chips.view_share', ['percent' => 100])), $html);
    }

    #[Test]
    public function render_givenViewShares_describesTheSelectedDungeonsViewsInTheReadout(): void
    {
        // Arrange
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);
        /** @var Dungeon $selected */
        $selected   = $dungeons->get(1);
        $viewShares = collect([$dungeons->first()->id => 1.0, $selected->id => 0.5]);
        $views      = e(__('view_common.dungeon.list.chips.view_share', ['percent' => 50]));

        // Act
        $html = $this->renderList(GameVersion::GAME_VERSION_CLASSIC_ERA, $dungeons, $selected->key, $viewShares);

        // Assert
        $this->assertMatchesRegularExpression('/<div class="dungeon_strip_readout"[^>]*data-view-share="0.5"/', $html);
        $this->assertMatchesRegularExpression(sprintf('/<span class="dungeon_strip_readout_views">%s<\/span>/', preg_quote($views, '/')), $html);
    }

    #[Test]
    public function render_givenNoViewShares_rendersPlainChipsAndAnEmptyReadoutViews(): void
    {
        // Arrange
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);

        // Act
        $html = $this->renderList(GameVersion::GAME_VERSION_CLASSIC_ERA, $dungeons, $dungeons->first()->key);

        // Assert
        $this->assertStringContainsString('class="dungeon_strip_chip"', $html);
        $this->assertStringNotContainsString('dungeon_strip_chip--views', $html);
        $this->assertStringNotContainsString('--dungeon-strip-view-share', $html);
        $this->assertStringContainsString('<span class="dungeon_strip_readout_views"></span>', $html);
    }

    #[Test]
    public function header_givenPageViewCountsOnClassicEra_fillsTheViewedDungeonsChip(): void
    {
        // Arrange
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);
        /** @var Dungeon $viewed */
        $viewed    = $dungeons->get(1);
        $viewCount = PageViewCount::factory()->create([
            'model_class' => Dungeon::class,
            'model_id'    => $viewed->id,
            'source'      => Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON,
            'views'       => 100,
        ]);
        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_CLASSIC_ERA;

        try {
            // Act
            $html = view('common.layout.header')->render();

            // Assert
            $this->assertMatchesRegularExpression($this->getViewsChipPattern($viewed, __('view_common.dungeon.list.chips.most_viewed'), '100%', '1'), $html);
        } finally {
            unset($_COOKIE['game_version']);
            $viewCount->delete();
        }
    }

    #[Test]
    public function compose_givenPageViewCountsOnRetail_comparesNoViews(): void
    {
        // Arrange
        $this->actingAsGuest();
        /** @var Dungeon $viewed */
        $viewed    = $this->getDungeons(GameVersion::GAME_VERSION_RETAIL)->first();
        $viewCount = PageViewCount::factory()->create([
            'model_class' => Dungeon::class,
            'model_id'    => $viewed->id,
            'source'      => Dungeon::PAGE_VIEW_SOURCE_VIEW_DUNGEON,
            'views'       => 100,
        ]);
        $view = view('common.layout.header');

        try {
            // Act
            app(HeaderComposer::class)->compose($view);

            // Assert
            $this->assertTrue($view->getData()['dungeonContextViewShares']->isEmpty());
        } finally {
            $viewCount->delete();
        }
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

    /**
     * The abbreviation is all a retail tile shows at rest; its full name takes its place on hover and focus, and is
     * the one name a screen reader hears for the link - not the abbreviation, nor the image's alt on top of it.
     */
    #[Test]
    public function render_givenRetail_namesEveryTileLinkOnceByItsFullName(): void
    {
        // Arrange
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_RETAIL);

        // Act
        $html = $this->renderList(GameVersion::GAME_VERSION_RETAIL, $dungeons);

        // Assert
        $this->assertGreaterThan(0, $dungeons->count());
        foreach ($dungeons as $dungeon) {
            $this->assertMatchesRegularExpression(
                sprintf(
                    '/<a href="%s"\s*>\s*<span class="card-text text-white dungeon_card_dungeon_name"\s+aria-hidden="true"\s*>\s*%s\s*<\/span>\s*'
                    . '<span class="card-text text-white dungeon_card_dungeon_full_name">%s<\/span>\s*<img class="card-img-top"\s+src="[^"]*"\s+alt=""/',
                    preg_quote(sprintf('/link/%s', $dungeon->key), '/'),
                    preg_quote(e(__($dungeon->abbreviation)), '/'),
                    preg_quote(e(__($dungeon->name)), '/'),
                ),
                $html,
                sprintf('%s is not named once by its full name', $dungeon->key),
            );
        }
    }

    /**
     * A tile without an abbreviation (the next season's) keeps its label and image alt as its name, and gains no name
     * line.
     */
    #[Test]
    public function render_givenATileWithoutAFullName_keepsItsLabelAndImageAltAsItsName(): void
    {
        // Arrange
        $title    = __('view_common.dungeon.list.next_season');
        $imageAlt = 'Midnight';

        // Act
        $html = view('common.dungeon.list.card', [
            'link'       => '/next-season',
            'title'      => $title,
            'isSelected' => false,
            'imageUrl'   => '/next-season.jpg',
            'imageAlt'   => $imageAlt,
        ])->render();

        // Assert
        $this->assertMatchesRegularExpression(
            sprintf('/<span class="card-text text-white dungeon_card_dungeon_name"\s*>\s*%s\s*<\/span>/', preg_quote(e($title), '/')),
            $html,
        );
        $this->assertStringContainsString(sprintf('alt="%s"', $imageAlt), $html);
        $this->assertStringNotContainsString('dungeon_card_dungeon_full_name', $html);
        $this->assertStringNotContainsString('aria-hidden="true"', $html);
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
            $this->assertSame(1, preg_match('/<div class="offcanvas offcanvas-bottom dungeon_sheet.*<p class="dungeon_sheet_empty"/s', $html, $matches));
            $sheet = $matches[0];
            $this->assertSame($dungeons->count(), preg_match_all('/class="dungeon_sheet_row[" ]/', $sheet));
            $this->assertStringContainsString(e(__('view_common.dungeon.list.groups.dungeon')), $sheet);
            $this->assertStringContainsString(e(__('view_common.dungeon.list.groups.raid')), $sheet);
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    /**
     * The sheet's filter matches on what the row shows: the full name and the abbreviation right-aligned next to it.
     */
    #[Test]
    public function render_givenClassicEraOnMobile_givesEverySheetRowItsNameAndAbbreviationToFilterOn(): void
    {
        // Arrange
        $dungeons                = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA);
        $_COOKIE['game_version'] = GameVersion::GAME_VERSION_CLASSIC_ERA;

        try {
            // Act
            $html = view('common.layout.header')->render();

            // Assert
            $this->assertGreaterThan(0, $dungeons->count());
            foreach ($dungeons as $dungeon) {
                $this->assertStringContainsString(
                    sprintf('data-filter-text="%s"', e(mb_strtolower(sprintf('%s %s', __($dungeon->name), __($dungeon->abbreviation))))),
                    $html,
                    sprintf('%s has no filter text', $dungeon->key),
                );
            }
        } finally {
            unset($_COOKIE['game_version']);
        }
    }

    /**
     * A game version without raids (or without world maps) must not render an empty, labelled group.
     */
    #[Test]
    public function render_givenASeasonlessGameVersionWithoutRaids_rendersNoRaidGroup(): void
    {
        // Arrange
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA)->reject(static fn(Dungeon $dungeon) => $dungeon->raid)->values();

        // Act
        $html = $this->renderList(GameVersion::GAME_VERSION_CLASSIC_ERA, $dungeons);

        // Assert
        $this->assertSame(['dungeon'], array_keys($this->getChipLabelsByGroup($html)));
        $this->assertStringNotContainsString('dungeon_strip_group--raid', $html);
        $this->assertStringNotContainsString('dungeon_strip_group--world', $html);
    }

    #[Test]
    public function render_givenASeasonlessGameVersionWithoutRaidsOnMobile_rendersNoRaidGroupHeader(): void
    {
        // Arrange
        $dungeons = $this->getDungeons(GameVersion::GAME_VERSION_CLASSIC_ERA)->reject(static fn(Dungeon $dungeon) => $dungeon->raid)->values();

        // Act
        $html = $this->renderSheet(GameVersion::GAME_VERSION_CLASSIC_ERA, $dungeons);

        // Assert
        $this->assertSame(1, substr_count($html, 'class="dungeon_sheet_group_label"'));
        $this->assertStringContainsString('id="dungeon_sheet_group_dungeon"', $html);
        $this->assertStringNotContainsString('id="dungeon_sheet_group_raid"', $html);
        $this->assertStringNotContainsString(e(__('view_common.dungeon.list.groups.raid')), $html);
    }

    /**
     * The tier letter alone tells a screen reader nothing, and a tooltip on an element that takes no focus never
     * opens for the keyboard.
     */
    #[Test]
    public function render_givenRetailEaseTiers_makesEveryTierBadgeFocusableAndNamed(): void
    {
        // Arrange
        $dungeons       = $this->getDungeons(GameVersion::GAME_VERSION_RETAIL);
        $affixGroup     = new AffixGroup();
        $affixGroup->id = 1;
        $easeTiers      = collect([1 => $dungeons->mapWithKeys(static fn(Dungeon $dungeon) => [$dungeon->id => 'S'])]);

        // Act
        $html = view('common.dungeon.list', [
            'gameVersion'       => GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL),
            'dungeons'          => $dungeons,
            'useAbbreviation'   => true,
            'links'             => collect(),
            'easeTiers'         => $easeTiers,
            'currentAffixGroup' => $affixGroup,
        ])->render();

        // Assert
        $this->assertGreaterThan(0, $dungeons->count());
        $this->assertSame($dungeons->count(), preg_match_all(
            sprintf(
                '/<span class="dungeon_card_tier" tabindex="0" role="img" data-bs-toggle="tooltip"\s+title="[^"]+"\s+aria-label="%s">/',
                preg_quote(e(__('view_common.dungeon.list.card.this_week_tier_label', ['tier' => 'S'])), '/'),
            ),
            $html,
        ));
    }

    /**
     * A sheet row is a link, so the tier cannot take focus of its own: it is spelled out in the link's name instead.
     */
    #[Test]
    public function render_givenRetailEaseTiersOnMobile_spellsTheTierOutInsideEveryRowLink(): void
    {
        // Arrange
        $dungeons       = $this->getDungeons(GameVersion::GAME_VERSION_RETAIL);
        $affixGroup     = new AffixGroup();
        $affixGroup->id = 1;
        $easeTiers      = collect([1 => $dungeons->mapWithKeys(static fn(Dungeon $dungeon) => [$dungeon->id => 'A'])]);

        // Act
        $html = $this->renderSheet(GameVersion::GAME_VERSION_RETAIL, $dungeons, $easeTiers, $affixGroup);

        // Assert
        $this->assertGreaterThan(0, $dungeons->count());
        $this->assertSame($dungeons->count(), preg_match_all(
            sprintf(
                '/<a class="dungeon_sheet_row"(?:(?!<\/a>).)*<span class="visually-hidden">%s<\/span>(?:(?!<\/a>).)*<\/a>/s',
                preg_quote(e(__('view_common.dungeon.list.card.this_week_tier_label', ['tier' => 'A'])), '/'),
            ),
            $html,
        ));
    }

    /**
     * @return Collection<int, Dungeon>
     */
    private function getDungeons(string $gameVersionKey): Collection
    {
        return app(DungeonServiceInterface::class)->getDungeonsForGameVersion(GameVersion::firstWhere('key', $gameVersionKey));
    }

    /**
     * @param Collection<int, Dungeon>    $dungeons
     * @param Collection<int, float>|null $viewShares
     */
    private function renderList(string $gameVersionKey, Collection $dungeons, ?string $selected = null, ?Collection $viewShares = null): string
    {
        return view('common.dungeon.list', [
            'gameVersion'     => GameVersion::firstWhere('key', $gameVersionKey),
            'dungeons'        => $dungeons,
            'useAbbreviation' => true,
            'selected'        => $selected,
            'links'           => $dungeons->mapWithKeys(static fn(Dungeon $dungeon) => [$dungeon->key => sprintf('/link/%s', $dungeon->key)]),
            'viewShares'      => $viewShares ?? collect(),
        ])->render();
    }

    /**
     * @param Collection<int, Dungeon>                      $dungeons
     * @param Collection<int, Collection<int, string>>|null $easeTiers
     */
    private function renderSheet(string $gameVersionKey, Collection $dungeons, ?Collection $easeTiers = null, ?AffixGroup $currentAffixGroup = null): string
    {
        return view('common.layout.nav.dungeoncontext', [
            'gameVersion'       => GameVersion::firstWhere('key', $gameVersionKey),
            'dungeons'          => $dungeons,
            'selectedDungeon'   => null,
            'links'             => $dungeons->mapWithKeys(static fn(Dungeon $dungeon) => [$dungeon->key => sprintf('/link/%s', $dungeon->key)]),
            'easeTiers'         => $easeTiers ?? collect(),
            'currentAffixGroup' => $currentAffixGroup,
            'allGameVersions'   => collect(),
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

    /**
     * The abbreviations of the chips per group, in render order.
     *
     * @return array<string, array<int, string>>
     */
    private function getChipAbbreviationsByGroup(string $html): array
    {
        preg_match_all('/aria-labelledby="dungeon_strip_group_([a-z]+)".*?<\/div>\s*<\/div>/s', $html, $groups, PREG_SET_ORDER);

        $result = [];
        foreach ($groups as [$groupHtml, $group]) {
            preg_match_all('/<a class="dungeon_strip_chip[^>]*>([^<]*)<\/a>/', $groupHtml, $abbreviations);
            $result[$group] = array_map(static fn(string $abbreviation) => html_entity_decode($abbreviation, ENT_QUOTES), $abbreviations[1]);
        }

        return $result;
    }

    /**
     * A chip filled to $fill, describing its views as $views in its title and handing $viewShare to the readout.
     */
    private function getViewsChipPattern(Dungeon $dungeon, string $views, string $fill, string $viewShare): string
    {
        return sprintf(
            '/<a class="dungeon_strip_chip dungeon_strip_chip--views"\s+href="[^"]*"\s+aria-label="%1$s" title="%1$s - %2$s"\s+data-image="[^"]*"\s+data-view-share="%4$s" style="--dungeon-strip-view-share: %3$s"/',
            preg_quote(e(__($dungeon->name)), '/'),
            preg_quote(e($views), '/'),
            preg_quote($fill, '/'),
            preg_quote($viewShare, '/'),
        );
    }

    private function getStripHtml(string $html): string
    {
        $this->assertSame(1, preg_match('/<div class="dungeon_strip">.*?<button type="button" class="dungeon_strip_all"/s', $html, $matches));

        return $matches[0];
    }
}
