<?php

namespace App\Console\Commands\MapContext\Traits;

use App\Console\Commands\MapContext\Enums\MapContextScope;
use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use App\Service\Season\SeasonServiceInterface;
use Illuminate\Support\Collection;

/**
 * Resolves the `--scope` option shared by the map context generator commands into the set of
 * dungeon IDs that should be processed.
 */
trait ResolvesMapContextScope
{
    /**
     * Resolves the given `--scope` option value to the set of dungeon IDs it covers.
     *
     * Writes an error to the command's output and returns null if the scope value is not recognized.
     *
     * @return Collection<int, int>|null
     */
    protected function resolveDungeonIdsForScope(string $scope, SeasonServiceInterface $seasonService): ?Collection
    {
        $scopeEnum = MapContextScope::tryFrom($scope);

        if ($scopeEnum === null) {
            $this->error(sprintf(
                'Invalid --scope value "%s", expected one of: %s',
                $scope,
                implode(', ', array_column(MapContextScope::cases(), 'value')),
            ));

            return null;
        }

        $allDungeonIds = Dungeon::query()->pluck('id');

        return match ($scopeEnum) {
            MapContextScope::All  => $allDungeonIds,
            MapContextScope::Rest => $allDungeonIds
                ->diff($this->priorityDungeonIds($seasonService))
                ->values(),
            MapContextScope::Priority => $this->priorityDungeonIds($seasonService),
        };
    }

    /**
     * Dungeon IDs belonging to the current and next season of every expansion that currently has
     * an active season line - this includes non-retail game versions (e.g. Classic-era) whenever
     * they have one. The next season is included alongside the current one because it starts
     * receiving traffic before it goes live (e.g. previews, early route creation).
     *
     * @return Collection<int, int>
     */
    private function priorityDungeonIds(SeasonServiceInterface $seasonService): Collection
    {
        $dungeonIds = collect();

        foreach (GameVersion::active()->get() as $gameVersion) {
            if (!$gameVersion->has_seasons) {
                continue;
            }

            $currentSeason = $seasonService->getCurrentSeason($gameVersion->expansion);
            if ($currentSeason !== null) {
                $dungeonIds = $dungeonIds->merge($currentSeason->dungeons()->pluck('dungeons.id'));
            }

            $nextSeason = $seasonService->getNextSeasonOfExpansion($gameVersion->expansion);
            if ($nextSeason !== null) {
                $dungeonIds = $dungeonIds->merge($nextSeason->dungeons()->pluck('dungeons.id'));
            }
        }

        return $dungeonIds->unique()->values();
    }
}
