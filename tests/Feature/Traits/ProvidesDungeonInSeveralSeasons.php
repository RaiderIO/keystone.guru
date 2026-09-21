<?php

namespace Tests\Feature\Traits;

use App\Models\Dungeon;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Season;
use App\Repositories\Interfaces\SeasonRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

trait ProvidesDungeonInSeveralSeasons
{
    /**
     * A seeded dungeon that is part of more than one season, one of them older than the season a new route for
     * it would get and holding affix groups.
     *
     * @return array{Dungeon, Season, Season} The dungeon, an older season of it and its newest season
     */
    protected function findDungeonInSeveralSeasons(): array
    {
        $seasonRepository = app(SeasonRepositoryInterface::class);

        $dungeons = Dungeon::query()
            ->where('active', true)
            ->whereNotNull('challenge_mode_id')
            ->get();

        foreach ($dungeons as $dungeon) {
            if ($dungeon->getCurrentMappingVersion() === null) {
                continue;
            }

            $newestSeason = $seasonRepository->getNewestSeasonsForDungeons(collect([$dungeon->id]))->get($dungeon->id);
            if ($newestSeason === null) {
                continue;
            }

            /** @var Season|null $olderSeason */
            $olderSeason = Season::query()
                ->whereHas('dungeons', static fn(Builder $query) => $query->where('dungeons.id', $dungeon->id))
                ->whereHas('affixGroups')
                ->where('start', '<', $newestSeason->start)
                ->orderByDesc('start')
                ->first();

            if ($olderSeason !== null) {
                return [$dungeon, $olderSeason, $newestSeason];
            }
        }

        $this->fail('The seeded data holds no dungeon that is part of more than one season');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function createDungeonRouteInSeason(Dungeon $dungeon, ?Season $season, array $attributes = []): DungeonRoute
    {
        return DungeonRoute::factory()->create(array_merge([
            'dungeon_id'         => $dungeon->id,
            'mapping_version_id' => $dungeon->getCurrentMappingVersion()->id,
            'season_id'          => $season?->id,
        ], $attributes));
    }
}
