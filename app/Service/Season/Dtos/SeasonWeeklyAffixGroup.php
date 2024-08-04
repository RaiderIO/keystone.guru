<?php

namespace App\Service\Season\Dtos;

use App\Models\AffixGroup\AffixGroup;
use App\Models\Season;
use Carbon\Carbon;

readonly class SeasonWeeklyAffixGroup
{
    public function __construct(
        public AffixGroup $affixGroup,
        public int        $week,
        public Carbon     $date,
    ) {
    }
}
