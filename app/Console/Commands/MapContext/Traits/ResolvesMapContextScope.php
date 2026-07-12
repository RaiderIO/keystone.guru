<?php

namespace App\Console\Commands\MapContext\Traits;

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
    private const string SCOPE_CURRENT_SEASON = 'current-season';

    private const string SCOPE_REST = 'rest';

    private const string SCOPE_ALL = 'all';

    /** @var array<int, string> */
    private const array VALID_SCOPES = [
        self::SCOPE_CURRENT_SEASON,
        self::SCOPE_REST,
        self::SCOPE_ALL,
    ];

    /**
     * Resolves the given `--scope` option value to the set of dungeon IDs it covers.
     *
     * Writes an error to the command's output and returns null if the scope value is not recognized.
     *
     * @return Collection<int, int>|null
     */
    protected function resolveDungeonIdsForScope(string $scope, SeasonServiceInterface $seasonService): ?Collection
    {
        if (!in_array($scope, self::VALID_SCOPES, true)) {
            $this->error(sprintf(
                'Invalid --scope value "%s", expected one of: %s',
                $scope,
                implode(', ', self::VALID_SCOPES),
            ));

            return null;
        }

        $allDungeonIds = Dungeon::query()->pluck('id');

        return match ($scope) {
            self::SCOPE_ALL  => $allDungeonIds,
            self::SCOPE_REST => $allDungeonIds
                ->diff($this->currentSeasonDungeonIds($seasonService))
                ->values(),
            default => $this->currentSeasonDungeonIds($seasonService),
        };
    }

    /**
     * Dungeon IDs belonging to the current season of every expansion that currently has an active
     * season - this includes non-retail game versions (e.g. Classic-era) whenever they have one.
     *
     * @return Collection<int, int>
     */
    private function currentSeasonDungeonIds(SeasonServiceInterface $seasonService): Collection
    {
        $dungeonIds = collect();

        foreach (GameVersion::active()->get() as $gameVersion) {
            if (!$gameVersion->has_seasons) {
                continue;
            }

            $currentSeason = $seasonService->getCurrentSeason($gameVersion->expansion);

            if ($currentSeason === null) {
                continue;
            }

            $dungeonIds = $dungeonIds->merge($currentSeason->dungeons()->pluck('dungeons.id'));
        }

        return $dungeonIds->unique()->values();
    }
}
