<?php


namespace App\Service\Season;

use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface SeasonServiceInterface
{
    function getSeasons(): Collection;

    function getFirstSeason(): Season;

    function getSeasonAt(Carbon $date);

    function getCurrentSeason(): ?Season;

    function getIterationsAt(Carbon $date): int;

    function getAffixGroupIndexAt(Carbon $date): int;

    function getDisplayedAffixGroups(int $iterationOffset): Collection;
}