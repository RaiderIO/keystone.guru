<?php

namespace Tests\Feature\View\Common\Dungeon;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('DungeonGridTabs')]
final class DungeonGridTabsTest extends PublicTestCase
{
    private function classicEra(): GameVersion
    {
        return GameVersion::firstWhere('key', GameVersion::GAME_VERSION_CLASSIC_ERA);
    }

    /**
     * @return Collection<int, int> Ids of the expansion's dungeons (raid: false) or raids (raid: true) mapped for Classic Era.
     */
    private function instanceIds(string $expansionShortname, bool $raid): Collection
    {
        $expansion = Expansion::firstWhere('shortname', $expansionShortname);
        $relation  = $raid ? $expansion->raids() : $expansion->dungeons();

        return $relation->forGameVersion($this->classicEra())->pluck('dungeons.id');
    }

    private function renderGridTabs(?callable $filterFn = null): string
    {
        $gameVersion = $this->classicEra();

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
     * @param Collection<int, int> $dungeonIds
     * @param callable(): void     $callback
     */
    private function withDungeonsActive(Collection $dungeonIds, bool $active, callable $callback): void
    {
        $originalActive = Dungeon::query()
            ->whereIn('id', $dungeonIds)
            ->pluck('active', 'id');

        try {
            Dungeon::query()->whereIn('id', $dungeonIds)->update(['active' => $active]);

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
        // Arrange - with the newer expansion's raids off too, Classic's dungeon tab would have been the first tab
        $inactiveIds = $this->instanceIds(Expansion::EXPANSION_CLASSIC, false)
            ->merge($this->instanceIds(Expansion::EXPANSION_TBC, true));
        $this->assertTrue($this->instanceIds(Expansion::EXPANSION_CLASSIC, false)->isNotEmpty());

        $this->withDungeonsActive($inactiveIds, false, function (): void {
            // Act
            $html = $this->renderGridTabs();

            // Assert
            $this->assertStringNotContainsString(sprintf('id="%s-grid-tab"', Expansion::EXPANSION_CLASSIC), $html);
            $this->assertStringNotContainsString(sprintf('id="%s-grid-content"', Expansion::EXPANSION_CLASSIC), $html);
            $this->assertStringContainsString(sprintf('id="%s-raid-grid-tab"', Expansion::EXPANSION_CLASSIC), $html);
            $this->assertMatchesRegularExpression(
                sprintf('/id="%s-raid-grid-tab"\s+class="nav-link active"/', Expansion::EXPANSION_CLASSIC),
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
        // Arrange
        $dungeonIds = $this->instanceIds(Expansion::EXPANSION_CLASSIC, false);
        $this->assertTrue($dungeonIds->isNotEmpty());

        $this->withDungeonsActive($dungeonIds, true, function (): void {
            // Act
            $html = $this->renderGridTabs();

            // Assert
            $this->assertStringContainsString(sprintf('id="%s-grid-tab"', Expansion::EXPANSION_CLASSIC), $html);
            $this->assertStringContainsString(sprintf('id="%s-grid-content"', Expansion::EXPANSION_CLASSIC), $html);
            $this->assertStringContainsString(sprintf('id="%s-raid-grid-tab"', Expansion::EXPANSION_CLASSIC), $html);
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
