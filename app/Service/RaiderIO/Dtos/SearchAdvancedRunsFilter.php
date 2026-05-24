<?php

namespace App\Service\RaiderIO\Dtos;

use Illuminate\Support\Carbon;

class SearchAdvancedRunsFilter
{
    /**
     * @param int[] $specBlizzardIds Blizzard spec IDs to filter on (memberSpecIds). Empty means no spec filter.
     * @param int   $mythicLevelMin  Minimum keystone level.
     * @param int   $limit           Maximum number of results to return.
     * @param int   $offset          Pagination offset.
     */
    public function __construct(
        public readonly ?int    $dungeonZoneId,
        public readonly string  $season,
        public readonly array   $specBlizzardIds,
        public readonly Carbon  $completedAtFrom,
        public readonly ?Carbon $completedAtTo,
        public readonly int     $mythicLevelMin,
        public readonly int     $limit,
        public readonly int     $offset,
    ) {
    }
}
