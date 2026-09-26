<?php

namespace Tests\Feature\View\Common\Dungeon;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Models\RaidKey;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('DungeonGridTabs')]
final class DungeonGridTabsTest extends PublicTestCase
{
    /**
     * The Burning Crusade's only non-raid instances mapped for Classic Era.
     */
    private const array TBC_CLASSIC_DUNGEON_KEYS = [
        RaidKey::GRUULS_LAIR->value,
        RaidKey::MAGTHERIDONS_LAIR->value,
    ];

    private function renderGridTabs(?callable $filterFn = null): string
    {
        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);

        return view('common.dungeon.gridtabs', [
            'id'          => 'test_dungeon',
            'tabsId'      => 'test_dungeon_select_tabs',
            'gameVersion' => $gameVersion,
            'route'       => 'dungeon.explore.gameversion.view',
            'routeParams' => ['gameVersion' => $gameVersion],
            'filterFn'    => $filterFn,
        ])->render();
    }

    /**
     * @param callable(): void $callback
     */
    private function withTbcClassicDungeonsActive(bool $active, callable $callback): void
    {
        $originalActive = Dungeon::query()
            ->whereIn('key', self::TBC_CLASSIC_DUNGEON_KEYS)
            ->pluck('active', 'id');

        try {
            Dungeon::query()->whereIn('key', self::TBC_CLASSIC_DUNGEON_KEYS)->update(['active' => $active]);

            $callback();
        } finally {
            foreach ($originalActive as $id => $wasActive) {
                Dungeon::query()->whereKey($id)->update(['active' => $wasActive]);
            }
        }
    }

    /**
     * Scenario: an expansion has raids for the game version but its dungeons are all inactive - its dungeon
     * tab must not be rendered, or the selector opens on an empty page.
     */
    #[Test]
    public function render_givenExpansionWithOnlyInactiveDungeons_omitsItsDungeonTab(): void
    {
        $this->withTbcClassicDungeonsActive(false, function (): void {
            // Act
            $html = $this->renderGridTabs();

            // Assert
            $this->assertStringNotContainsString(sprintf('id="%s-grid-tab"', Expansion::EXPANSION_TBC), $html);
            $this->assertStringNotContainsString(sprintf('id="%s-grid-content"', Expansion::EXPANSION_TBC), $html);
            $this->assertStringContainsString(sprintf('id="%s-raid-grid-tab"', Expansion::EXPANSION_TBC), $html);
            $this->assertMatchesRegularExpression(
                sprintf('/id="%s-raid-grid-tab"\s+class="nav-link active"/', Expansion::EXPANSION_TBC),
                $html,
            );
        });
    }

    /**
     * Scenario: the expansion's dungeons are active for the game version, so they get their own tab.
     */
    #[Test]
    public function render_givenExpansionWithActiveDungeons_rendersItsDungeonTab(): void
    {
        $this->withTbcClassicDungeonsActive(true, function (): void {
            // Act
            $html = $this->renderGridTabs();

            // Assert
            $this->assertStringContainsString(sprintf('id="%s-grid-tab"', Expansion::EXPANSION_TBC), $html);
            $this->assertStringContainsString(sprintf('id="%s-grid-content"', Expansion::EXPANSION_TBC), $html);
            $this->assertStringContainsString(sprintf('id="%s-raid-grid-tab"', Expansion::EXPANSION_TBC), $html);
        });
    }

    /**
     * Scenario: the page's filter excludes every dungeon - no expansion tab is rendered at all.
     */
    #[Test]
    public function render_givenFilterExcludingEveryDungeon_rendersNoExpansionTabs(): void
    {
        // Act
        $html = $this->renderGridTabs(static fn(Dungeon $dungeon) => false);

        // Assert
        $this->assertStringNotContainsString('-grid-tab"', $html);
        $this->assertStringNotContainsString('role="tabpanel"', $html);
    }
}
