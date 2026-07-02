<?php

namespace App\Service\RaiderIO\Dtos;

use App\Models\CharacterClassSpecialization;
use App\Models\Dungeon;
use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

readonly class SearchAdvancedRunsFilter
{
    /**
     * @param ?Dungeon                                      $dungeon        Null means no dungeon restriction (all season dungeons).
     * @param Collection<int, CharacterClassSpecialization> $specs          Specs to filter on. Empty collection means no spec filter.
     * @param int                                           $mythicLevelMin Minimum keystone level.
     * @param int                                           $limit          Maximum number of results per page.
     * @param int                                           $offset         Pagination offset.
     */
    public function __construct(
        public ?Dungeon   $dungeon,
        public Season     $season,
        public Collection $specs,
        public Carbon     $completedAtFrom,
        public ?Carbon    $completedAtTo,
        public int        $mythicLevelMin,
        public int        $limit,
        public int        $offset,
    ) {
    }
}
