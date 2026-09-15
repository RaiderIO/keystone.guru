<?php

namespace Tests\Feature\Controller\Dungeon;

use App\Models\Floor\Floor;
use Illuminate\Database\Events\QueryExecuted;
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

    #[Test]
    public function viewDungeonFloor_givenAnActiveDungeon_resolvesSeasonsWithoutAQueryPerWeek(): void
    {
        // Arrange
        [$dungeon, $mappingVersion] = $this->findDungeon(facadeEnabled: false, dungeonActive: true, requireDefaultFloor: true);
        $gameVersion                = $mappingVersion->gameVersion;
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
        $this->assertLessThanOrEqual(
            10,
            count($seasonQueries),
            sprintf(
                'Expected the seasons of an expansion to be resolved in memory, got %d queries: %s',
                count($seasonQueries),
                implode(' | ', array_unique($seasonQueries)),
            ),
        );
    }
}
