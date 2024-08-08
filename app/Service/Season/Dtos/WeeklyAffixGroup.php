<?php

namespace App\Service\Season\Dtos;

use App\Models\AffixGroup\AffixGroup;
use Illuminate\Support\Carbon;

readonly class WeeklyAffixGroup
{
    public function __construct(
        public AffixGroup $affixGroup,
        public int        $week,
        public Carbon     $date,
    ) {
    }
}
