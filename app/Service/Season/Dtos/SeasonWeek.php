<?php

namespace App\Service\Season\Dtos;

use Illuminate\Support\Carbon;

readonly class SeasonWeek
{
    public function __construct(
        /** The week's index within its season, where 1 is the week the season starts in. */
        public int    $week,
        /** The keystone leaderboard period this week falls in - the number a run carries in its metadata. */
        public int    $period,
        /** The weekly reset this week starts at, in UTC. */
        public Carbon $start,
    ) {
    }
}
