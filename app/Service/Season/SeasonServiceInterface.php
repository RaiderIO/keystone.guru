<?php


namespace App\Service\Season;

use App\Models\Expansion;
use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface SeasonServiceInterface
{
    function getSeasons(?Expansion $expansion = null): Collection;

    function getFirstSeason(?Expansion $expansion = null): Season;

    function getSeasonAt(Carbon $date, ?Expansion $expansion = null);

    function getCurrentSeason(?Expansion $expansion = null): ?Season;

    function getIterationsAt(Carbon $date, ?Expansion $expansion = null): int;

    function getAffixGroupIndexAt(Carbon $date, ?Expansion $expansion = null): int;

    function getDisplayedAffixGroups(int $iterationOffset): Collection;
}
