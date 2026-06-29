<?php

namespace App\Service\Season;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\GameServerRegion;
use App\Models\Season;
use App\Repositories\Interfaces\SeasonRepositoryInterface;
use App\Service\Expansion\ExpansionService;
use App\Traits\UserCurrentTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * This service provides functionality for reading the current laravel echo service and parsing its contents.
 *
 * @author Wouter
 *
 * @since 17/06/2019
 */
class SeasonService implements SeasonServiceInterface
{
    use UserCurrentTime;

    /**
     * @var Collection<int, Season>
     */
    private Collection $seasonCache;

    private ?Season $firstSeasonCache = null;

    public function __construct(
        private readonly ExpansionService          $expansionService,
        private readonly SeasonRepositoryInterface $seasonRepository,
    ) {
        $this->seasonCache      = collect();
        $this->firstSeasonCache = null;
    }

    /**
     * @return Collection<int, Season>
     */
    public function getSeasons(?Expansion $expansion = null, ?GameServerRegion $region = null): Collection
    {
        $expansion ??= $this->expansionService->getCurrentExpansion(
            $region ?? GameServerRegion::getUserOrDefaultRegion(),
        );

        return $this->getAllSeasons()->where('expansion_id', $expansion->id);
    }

    /**
     * @return Collection<int, Season>
     */
    public function getAllSeasons(): Collection
    {
        $this->ensureSeasonCacheLoaded();

        return $this->seasonCache;
    }

    public function getFirstSeason(): Season
    {
        if ($this->firstSeasonCache === null) {
            $this->firstSeasonCache = Season::selectRaw('seasons.*')
                ->leftJoin('timewalking_events', 'timewalking_events.expansion_id', 'seasons.expansion_id')
                ->whereNull('timewalking_events.id')
                ->orderBy('seasons.start')
                ->limit(1)
                ->first();
        }

        return $this->firstSeasonCache;
    }

    public function getNextSeason(Season $season, ?GameServerRegion $region = null): ?Season
    {
        $seasons = $this->getSeasons($season->expansion, $region);

        foreach ($seasons as $seasonCandidate) {
            if ($seasonCandidate->start->isAfter($season->start)) {
                return $seasonCandidate;
            }
        }

        return null;
    }

    /**
     * Find the season active at a given date across all expansions, skipping seasons with no affix groups defined.
     * Unlike getSeasonAt(), this is not scoped to a single expansion and filters out placeholder seasons that have
     * not yet had their affix groups assigned, so an upcoming season never blanks the rotation display.
     */
    public function findSeasonWithAffixGroupsAt(Carbon $date, GameServerRegion $region): ?Season
    {
        $result = null;

        foreach ($this->getAllSeasons() as $season) {
            if ($season->start($region)->gt($date)) {
                break;
            }

            if ($season->affix_group_count <= 0 || $season->affixGroups->isEmpty()) {
                continue;
            }

            $result = $season;
        }

        return $result;
    }

    /**
     * Get the season that was active at a specific date.
     */
    public function getSeasonAt(Carbon $date, ?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season
    {
        $region ??= GameServerRegion::getUserOrDefaultRegion();
        $expansion ??= $this->expansionService->getCurrentExpansion($region);

        /** @var Season|null $season */
        $season = Season::whereRaw(
            'DATE_ADD(DATE_ADD(`start`, INTERVAL ? day), INTERVAL ? hour) <= ?',
            [
                $region->reset_day_offset,
                $region->reset_hours_offset,
                // Database stores everything in UTC, so we need to convert the date to UTC to compare it properly
                $date->copy()->setTimezone('UTC')->toDateTimeString(),
            ],
        )
            ->where('expansion_id', $expansion->id)
            ->orderBy('start', 'desc')
            ->first();

        return $season;
    }

    /**
     * @param  Expansion|null $expansion The expansion you want the current season for - or null to get it for the current expansion.
     * @return Season|null    The season that's currently active, or null if none is active at this time.
     */
    public function getCurrentSeason(?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season
    {
        $region ??= GameServerRegion::getUserOrDefaultRegion();
        $expansion ??= $this->expansionService->getCurrentExpansion($region);

        return $this->getSeasonAt(Carbon::now(), $expansion, $region);
    }

    public function getNextSeasonOfExpansion(?Expansion $expansion = null, ?GameServerRegion $region = null): ?Season
    {
        $region ??= GameServerRegion::getUserOrDefaultRegion();
        $expansion ??= $this->expansionService->getCurrentExpansion($region);

        return $expansion->nextSeason($region);
    }

    public function getMostRecentSeasonForDungeon(Dungeon $dungeon): ?Season
    {
        if (!$dungeon->hasMappingVersionWithSeasons()) {
            return null;
        }

        return $this->seasonRepository->getMostRecentSeasonForDungeon($dungeon);
    }

    public function getUpcomingSeasonForDungeon(Dungeon $dungeon): ?Season
    {
        if (!$dungeon->hasMappingVersionWithSeasons()) {
            return null;
        }

        return $this->seasonRepository->getUpcomingSeasonForDungeon($dungeon);
    }

    public function getSeasonFromShortString(?string $season): ?Season
    {
        if ($season === null) {
            return null;
        }

        $result = null;

        $split = explode('-', $season);
        if (count($split) === 3) {
            $expansionShortName = $split[1];
            $seasonIndex        = (int)$split[2];

            $expansion = Expansion::where('shortname', $expansionShortName)->first();
            if ($expansion !== null) {
                $result = Season::where('expansion_id', $expansion->id)
                    ->where('index', $seasonIndex)
                    ->first();
            }
        }

        return $result;
    }

    private function ensureSeasonCacheLoaded(): void
    {
        if ($this->seasonCache->empty()) { // @phpstan-ignore if.alwaysTrue
            $this->seasonCache = Season::selectRaw('seasons.*')
                ->with(['expansion', 'expansion.timewalkingEvent', 'affixGroups'])
                ->leftJoin('timewalking_events', 'timewalking_events.expansion_id', 'seasons.expansion_id')
                ->whereNull('timewalking_events.id')
                ->orderBy('seasons.start')
                ->get();
        }
    }
}
