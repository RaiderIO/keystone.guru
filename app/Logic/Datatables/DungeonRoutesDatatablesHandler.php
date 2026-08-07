<?php
/**
 * Created by PhpStorm.
 * User: wouterk
 * Date: 20-11-2018
 * Time: 15:22
 */

namespace App\Logic\Datatables;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Mapping\MappingVersion;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Override;

class DungeonRoutesDatatablesHandler extends DatatablesHandler
{
    #[Override]
    /**
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        /**
         * @var array<string, mixed> $result
         */
        $result = parent::getResult();

        $latestMappingVersionIdsByDungeonAndGameVersion = $this->getLatestMappingVersionIdsByDungeonAndGameVersion();

        $result['data'] = $result['data']->each(function (DungeonRoute $dungeonRoute) use ($latestMappingVersionIdsByDungeonAndGameVersion) {
            $dungeonRoute->makeHidden(['mappingVersion']);
            $dungeonRoute->dungeon->makeHidden(['gameVersion']);
            $dungeonRoute->dungeon->floors->each(function (Floor $floor) {
                $floor->setVisible([
                    'active',
                    'index',
                    'facade',
                ]);
            });

            // Stamped here instead of in the SQL select - see the comment above the query in
            // AjaxDungeonRouteController::get() for why. Cast to int so this always matches the
            // native int type of mapping_version_id, whichever route is up to date or not.
            // mapping_version_game_version_id is selected there directly off the joined mapping_versions
            // row (not via the mappingVersion relation, which isn't eager loaded on this endpoint and
            // would trip preventLazyLoading).
            $groupKey = sprintf('%d:%d', $dungeonRoute->dungeon_id, $dungeonRoute->mapping_version_game_version_id);
            $dungeonRoute->setAttribute(
                'dungeon_latest_mapping_version_id',
                (int)($latestMappingVersionIdsByDungeonAndGameVersion[$groupKey] ?? $dungeonRoute->mapping_version_id),
            );
        });

        return $result;
    }

    protected function calculateRecordsTotal(): int
    {
        // Clear them
        $countQuery = $this->builder->getQuery()
            ->cloneWithout([
                'havings',
                'groups',
            ])
            // ->cloneWithoutBindings(['select'])
            ->selectRaw('count(distinct dungeon_routes.id) as aggregate');
        // Get the count
        $result = $countQuery->get(['aggregate']);
        // Returns an array with numbers, sum the entries to get the actual count. Again, a hack but it works for now.
        $recordsTotal = 0;
        foreach ($result as $countResult) {
            $recordsTotal += $countResult->aggregate;
        }

        return $recordsTotal;
    }

    protected function calculateRecordsFiltered(): ?int
    {
        // Count without limit first
        // I tried with SQL_CALC_FOUND_ROWS but that doesn't really work with Laravel pumping out more queries,
        // then FOUND_ROWS() would return the result from the wrong function, rather annoying that is.
        // Bit of a hack, but for now the only way to reliably get the pre-limit count.
        $query = $this->builder->getQuery()
            ->cloneWithout([
                'columns',
                'offset',
                'limit',
            ])->cloneWithoutBindings(['select'])
            ->selectRaw(DB::raw('count( distinct dungeon_routes.id) as aggregate')->getValue($this->builder->getGrammar()));
        // Temp store; it messes with the count
        $havings        = $query->havings;
        $query->havings = null;

        $query->orders = null;
        $countResults  = $query->get();
        // Restore
        $query->havings = $havings;

        // Returns an array with numbers, sum the entries to get the actual count. Again, a hack but it works for now.
        $recordsFiltered = 0;
        foreach ($countResults as $countResult) {
            $recordsFiltered += $countResult->aggregate;
        }

        return $recordsFiltered;
    }

    /**
     * Maps every "{dungeon_id}:{game_version_id}" pair to the id of the mapping_versions row with the
     * highest `version` for that pair - i.e. the id of the "latest" mapping version for that dungeon/game
     * version, using the same max(version) convention as Dungeon::getCurrentMappingVersionForGameVersion()
     * and MappingVersion::isLatestForDungeon().
     *
     * Done as a GROUP BY + MAX() self-join rather than a per-row correlated subquery in the main query:
     * MySQL materializes the GROUP BY result before applying ORDER BY/LIMIT, so a correlated subquery there
     * would run once per pre-LIMIT grouped route of the whole filtered set on this public, unauthenticated
     * endpoint - not once per the handful of rows actually returned. This query runs once, over
     * mapping_versions alone (a small table), regardless of how many dungeon_routes are filtered/paged.
     * The self-join predates #3717, when local dev still ran MySQL 5.7 and window functions were off
     * the table. Local dev and CI are on 8.0 now, so a ROW_NUMBER() OVER (PARTITION BY ...) rewrite is
     * available there - kept as-is here because it is correct and already runs once over a small table.
     *
     * @return array<string, int>
     */
    private function getLatestMappingVersionIdsByDungeonAndGameVersion(): array
    {
        $rows = MappingVersion::query()
            ->select(['mapping_versions.dungeon_id', 'mapping_versions.game_version_id'])
            ->selectRaw('MAX(mapping_versions.id) as id')
            ->joinSub(
                MappingVersion::query()->selectRaw('dungeon_id, game_version_id, MAX(version) as max_version')->groupBy(['dungeon_id', 'game_version_id']),
                'latest_mapping_versions',
                function (JoinClause $join) {
                    $join->on('mapping_versions.dungeon_id', '=', 'latest_mapping_versions.dungeon_id')
                        ->on('mapping_versions.game_version_id', '=', 'latest_mapping_versions.game_version_id')
                        ->on('mapping_versions.version', '=', 'latest_mapping_versions.max_version');
                },
            )
            ->groupBy(['mapping_versions.dungeon_id', 'mapping_versions.game_version_id'])
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[sprintf('%d:%d', $row->dungeon_id, $row->game_version_id)] = (int)$row->id;
        }

        return $result;
    }
}
