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

        $latestMappingVersionIdsByMappingVersionId = $this->getLatestMappingVersionIdsByMappingVersionId();

        $result['data'] = $result['data']->each(function (DungeonRoute $dungeonRoute) use ($latestMappingVersionIdsByMappingVersionId) {
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
            $dungeonRoute->setAttribute(
                'dungeon_latest_mapping_version_id',
                (int)($latestMappingVersionIdsByMappingVersionId[$dungeonRoute->mapping_version_id] ?? $dungeonRoute->mapping_version_id),
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
     * Maps every mapping_versions.id to the id of the mapping_versions row with the highest `version`
     * sharing the same (dungeon_id, game_version_id) pair - i.e. the id of the "latest" mapping version
     * for that dungeon/game version, using the same max(version) convention as
     * Dungeon::getCurrentMappingVersionForGameVersion() and MappingVersion::isLatestForDungeon().
     *
     * mapping_versions is a small table (~550 rows total today), so fetching it in full and grouping
     * in PHP is one cheap query regardless of how many dungeon_routes are filtered/paged - unlike a
     * per-row correlated subquery, which MySQL would run once per pre-LIMIT grouped route.
     *
     * @return array<int, int>
     */
    private function getLatestMappingVersionIdsByMappingVersionId(): array
    {
        /** @var array<string, array{id: int, version: int}> $latestByDungeonAndGameVersion */
        $latestByDungeonAndGameVersion = [];
        /** @var array<int, string> $groupKeyByMappingVersionId */
        $groupKeyByMappingVersionId = [];

        MappingVersion::query()
            ->without('gameVersion')
            ->select(['id', 'dungeon_id', 'game_version_id', 'version'])
            ->get()
            ->each(function (MappingVersion $mappingVersion) use (&$latestByDungeonAndGameVersion, &$groupKeyByMappingVersionId) {
                $groupKey                                        = sprintf('%d:%d', $mappingVersion->dungeon_id, $mappingVersion->game_version_id);
                $groupKeyByMappingVersionId[$mappingVersion->id] = $groupKey;

                $current = $latestByDungeonAndGameVersion[$groupKey] ?? null;
                if ($current === null
                    || $mappingVersion->version > $current['version']
                    || ($mappingVersion->version === $current['version'] && $mappingVersion->id > $current['id'])
                ) {
                    $latestByDungeonAndGameVersion[$groupKey] = [
                        'id'      => $mappingVersion->id,
                        'version' => $mappingVersion->version,
                    ];
                }
            });

        $result = [];
        foreach ($groupKeyByMappingVersionId as $mappingVersionId => $groupKey) {
            $result[$mappingVersionId] = $latestByDungeonAndGameVersion[$groupKey]['id'];
        }

        return $result;
    }
}
