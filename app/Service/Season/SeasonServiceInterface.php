<?php


namespace App\Service\Season;

use App\Models\Expansion;
use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface SeasonServiceInterface
{
    function getSeasons(?Expansion $expansion = null): Collection;

    function getFirstSeason(): Season;

    function getSeasonAt(Carbon $date, ?Expansion $expansion = null);

    function getCurrentSeason(?Expansion $expansion = null): ?Season;

    function getIterationsAt(Carbon $date): int;

    function getAffixGroupIndexAt(Carbon $date): int;

    function getDisplayedAffixGroups(int $iterationOffset): Collection;
}
