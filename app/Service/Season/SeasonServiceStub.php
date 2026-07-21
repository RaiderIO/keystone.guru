<?php

namespace App\Service\Season;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Provides a simple non-working implementation for the SeasonServiceInterface - in case we don't need the actual implementation
 *
 * @author Wouter
 *
 * @since 04/03/2025
 */
class SeasonServiceStub implements SeasonServiceInterface
{
    public function getSeasons(?Expansion $expansion = null, ?GameServerRegion $region = null): Collection
    {
        return collect();
    }

    public function getAllSeasons(): Collection
    {
        return collect();
    }

    public function getFirstSeason(): Season
    {
        return new Season();
    }

    public function getNextSeason(Season $season, ?GameServerRegion $region = null): ?Season
    {
        return null;
    }

    public function getSeasonAt(Carbon $date, ?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season
    {
        return null;
    }

    public function getCurrentSeason(?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season
    {
        return null;
    }

    public function getNextSeasonOfExpansion(?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season
    {
        return null;
    }

    public function getMostRecentSeasonForDungeon(Dungeon $dungeon): ?Season
    {
        return null;
    }

    public function getUpcomingSeasonForDungeon(Dungeon $dungeon): ?Season
    {
        return null;
    }

    public function getCurrentSeasonForDungeon(Dungeon $dungeon): ?Season
    {
        return null;
    }

    public function getSeasonFromShortString(string $season): ?Season
    {
        return null;
    }

    public function findSeasonWithAffixGroupsAt(Carbon $date, GameServerRegion $region): ?Season
    {
        return null;
    }
}
