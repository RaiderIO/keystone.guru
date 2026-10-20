<?php

namespace Tests\Feature\View\Common\Dungeon;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameVersion\GameVersion;
use App\Service\View\ViewServiceInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCases\PublicTestCase;

#[Group('View')]
#[Group('DungeonGridTabs')]
final class DungeonGridTabsQueryCountTest extends PublicTestCase
{
    /**
     * @return array<int, string> The SQL of every query that lists dungeons by their mapping versions' game version.
     */
    private function captureDungeonListQueries(callable $callback): array
    {
        /** @var array<int, string> $dungeonListQueries */
        $dungeonListQueries = [];
        DB::listen(static function (QueryExecuted $query) use (&$dungeonListQueries): void {
            if (str_contains($query->sql, 'from `dungeons`') && str_contains($query->sql, '`mapping_versions`')) {
                $dungeonListQueries[] = $query->sql;
            }
        });

        $callback();

        return $dungeonListQueries;
    }

    /**
     * @return array<string, array<int, int>> Dungeon ids per expansion tab key, in the order the tabs and their dungeons render.
     */
    private function parseExpansionTabs(string $html): array
    {
        $parts = preg_split('/<div id="([^"]+)-grid-content"/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        $result = [];
        for ($i = 1; $i < count($parts); $i += 2) {
            $tabKey = $parts[$i];
            if (str_starts_with($tabKey, 'season-')) {
                continue;
            }

            preg_match_all('/data-id="(\d+)"/', $parts[$i + 1], $matches);
            $result[$tabKey] = array_map(intval(...), $matches[1]);
        }

        return $result;
    }

    /**
     * @return array<string, array<int, int>> Dungeon ids per expansion tab key, built from each expansion's own relations.
     */
    private function expectedExpansionTabs(GameVersion $gameVersion): array
    {
        /** @var Collection<int, Expansion> $activeExpansions */
        $activeExpansions = app(ViewServiceInterface::class)->getActiveExpansions();

        $result = [];
        foreach ($activeExpansions as $expansion) {
            $dungeonIds = $expansion->dungeons()->active()->forGameVersion($gameVersion)->pluck('dungeons.id')->all();
            if (!empty($dungeonIds)) {
                $result[$expansion->key] = $dungeonIds;
            }

            $raidIds = $expansion->raids()->active()->forGameVersion($gameVersion)->pluck('dungeons.id')->all();
            if (!empty($raidIds)) {
                $result[sprintf('%s-raid', $expansion->key)] = $raidIds;
            }
        }

        return $result;
    }

    #[Test]
    #[DataProvider('selectRouteProvider')]
    public function select_givenGameVersionWithManyExpansions_queriesDungeonListOnce(string $routeName): void
    {
        // Arrange
        $gameVersion = GameVersion::firstWhere('key', GameVersion::GAME_VERSION_RETAIL);
        $this->assertGreaterThan(1, app(ViewServiceInterface::class)->getActiveExpansions()->count());
        $url = route($routeName, ['gameVersion' => $gameVersion]);

        // Act
        $response           = null;
        $dungeonListQueries = $this->captureDungeonListQueries(function () use (&$response, $url): void {
            $response = $this->get($url);
        });

        // Assert
        $response->assertOk();
        $this->assertCount(1, $dungeonListQueries, implode(PHP_EOL, $dungeonListQueries));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function selectRouteProvider(): array
    {
        return [
            'explore' => ['dungeon.explore.gameversion.select'],
            'heatmap' => ['dungeon.heatmap.gameversion.select'],
        ];
    }

    #[Test]
    #[DataProvider('gameVersionKeyProvider')]
    public function render_givenGameVersion_rendersEachExpansionsActiveInstancesInNameOrder(string $gameVersionKey): void
    {
        // Arrange
        $gameVersion = GameVersion::firstWhere('key', $gameVersionKey);
        $initialTabs = $this->expectedExpansionTabs($gameVersion);
        $this->assertNotEmpty($initialTabs);
        $inactiveDungeon = Dungeon::findOrFail(reset($initialTabs)[0]);

        try {
            Dungeon::query()->whereKey($inactiveDungeon->id)->update(['active' => false]);
            $expected = $this->expectedExpansionTabs($gameVersion);

            // Act
            $html = view('common.dungeon.gridtabs', [
                'id'          => 'test_dungeon',
                'tabsId'      => 'test_dungeon_select_tabs',
                'gameVersion' => $gameVersion,
                'route'       => 'dungeon.explore.gameversion.view',
                'routeParams' => ['gameVersion' => $gameVersion],
            ])->render();

            // Assert
            $this->assertSame($expected, $this->parseExpansionTabs($html));
            $this->assertNotContains($inactiveDungeon->id, array_merge(...array_values($expected)));
        } finally {
            Dungeon::query()->whereKey($inactiveDungeon->id)->update(['active' => $inactiveDungeon->active]);
        }
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function gameVersionKeyProvider(): array
    {
        return [
            'retail'      => [GameVersion::GAME_VERSION_RETAIL],
            'classic era' => [GameVersion::GAME_VERSION_CLASSIC_ERA],
        ];
    }
}
