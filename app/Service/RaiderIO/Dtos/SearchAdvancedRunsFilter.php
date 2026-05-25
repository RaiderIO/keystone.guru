<?php

namespace App\Service\RaiderIO\Dtos;

use App\Models\CharacterClassSpecialization;
use App\Models\Dungeon;
use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SearchAdvancedRunsFilter
{
    /**
     * @param ?Dungeon                                      $dungeon        Null means no dungeon restriction (all season dungeons).
     * @param Collection<int, CharacterClassSpecialization> $specs          Specs to filter on. Empty collection means no spec filter.
     * @param int                                           $mythicLevelMin Minimum keystone level.
     * @param int                                           $limit          Maximum number of results per page.
     * @param int                                           $offset         Pagination offset.
     */
    public function __construct(
        public readonly ?Dungeon   $dungeon,
        public readonly Season     $season,
        public readonly Collection $specs,
        public readonly Carbon     $completedAtFrom,
        public readonly ?Carbon    $completedAtTo,
        public readonly int        $mythicLevelMin,
        public readonly int        $limit,
        public readonly int        $offset,
    ) {
    }
}
