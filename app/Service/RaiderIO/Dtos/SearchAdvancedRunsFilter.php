<?php

namespace App\Service\RaiderIO\Dtos;

use App\Models\CharacterClassSpecialization;
use App\Models\Dungeon;
use App\Models\Faction;
use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

readonly class SearchAdvancedRunsFilter
{
    /**
     * @param ?Dungeon                                      $dungeon        Null means no dungeon restriction (all season dungeons).
     * @param Collection<int, CharacterClassSpecialization> $specs          Specs to filter on. Empty collection means no spec filter.
     * @param int                                           $mythicLevelMin Minimum keystone level.
     * @param ?int                                          $mythicLevelMax Maximum keystone level. Null means no upper bound.
     * @param int                                           $limit          Maximum number of results per page.
     * @param int                                           $offset         Pagination offset.
     * @param ?Faction                                      $faction        Restrict to groups where every member is of this faction. Null means no faction filter.
     *                                                                      Note that cross faction groups carry no faction at all and are excluded by any faction filter.
     */
    public function __construct(
        public ?Dungeon   $dungeon,
        public Season     $season,
        public Collection $specs,
        public Carbon     $completedAtFrom,
        public ?Carbon    $completedAtTo,
        public int        $mythicLevelMin,
        public ?int       $mythicLevelMax,
        public int        $limit,
        public int        $offset,
        public ?Faction   $faction = null,
    ) {
    }
}
