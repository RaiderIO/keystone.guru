<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\GameServerRegion;
use App\Models\Mapping\MappingVersion;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Traits\ProvidesDungeon;
use Tests\TestCases\PublicTestCase;

/**
 * Guards #4587: the affix table on the explore page walks every week since the season started and asked
 * the database which season each of those dates belonged to, which was 148 of the page's 217 queries.
 */
#[Group('Controller')]
#[Group('DungeonExplore')]
final class DungeonExploreControllerSeasonLookupTest extends PublicTestCase
{
    use ProvidesDungeon;

    private const int MAX_SEASON_QUERIES = 10;

    #[Test]
    public function viewDungeonFloor_givenAnActiveDungeon_resolvesSeasonsWithoutAQueryPerWeek(): void
    {
        // Arrange - the page only walks the weeks of a dungeon with an active season, and a query per week
        // only exceeds the limit once there are more weeks than that; a dungeon without either keeps this
        // green whatever the controller does
        $seasonService           = app(SeasonServiceInterface::class);
        $seasonAffixGroupService = app(SeasonAffixGroupServiceInterface::class);
        $region                  = GameServerRegion::getUserOrDefaultRegion();

        [$dungeon, $mappingVersion, $weeklyAffixGroupCount] = $this->findDungeon(
            facadeEnabled:       false,
            dungeonActive:       true,
            requireDefaultFloor: true,
            resolve:             static function (Dungeon $dungeon, MappingVersion $mappingVersion) use ($seasonService, $seasonAffixGroupService, $region): ?int {
                $season = $dungeon->getActiveSeason($seasonService);
                if ($season === null || !$dungeon->hasMappingVersionWithSeasons()) {
                    return null;
                }

                $weeks = $seasonAffixGroupService->getWeeklyAffixGroupsSinceStart($season, $region)->count();

                return $weeks > self::MAX_SEASON_QUERIES ? $weeks : null;
            },
        );
        $gameVersion = $mappingVersion->gameVersion;
        /** @var Floor $floor */
        $floor = Floor::where('dungeon_id', $dungeon->id)->defaultOrFacade($mappingVersion)->first();

        $url = route('dungeon.explore.gameversion.view.floor', [
            'gameVersion' => $gameVersion,
            'dungeon'     => $dungeon,
            'floorIndex'  => $floor->index,
        ]);

        // A cold request populates the caches this page shares with every other one, so that the
        // count below is the page's own cost rather than the suite's first-request cost
        $this->get($url);

        /** @var array<int, string> $seasonQueries */
        $seasonQueries = [];
        DB::listen(static function (QueryExecuted $query) use (&$seasonQueries): void {
            if (str_contains($query->sql, 'from `seasons`')) {
                $seasonQueries[] = $query->sql;
            }
        });

        // Act
        $response = $this->get($url);

        // Assert
        $response->assertOk();
        /** @var Collection<int, mixed> $seasonWeeklyAffixGroups */
        $seasonWeeklyAffixGroups = $response->viewData('seasonWeeklyAffixGroups');
        $this->assertCount($weeklyAffixGroupCount, $seasonWeeklyAffixGroups, 'The page did not walk the weeks of the season');
        $this->assertLessThanOrEqual(
            self::MAX_SEASON_QUERIES,
            count($seasonQueries),
            sprintf(
                'Expected the seasons of an expansion to be resolved in memory, got %d queries: %s',
                count($seasonQueries),
                implode(' | ', array_unique($seasonQueries)),
            ),
        );
    }
}
