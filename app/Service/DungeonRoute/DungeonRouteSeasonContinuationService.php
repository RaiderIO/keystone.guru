<?php

namespace App\Service\DungeonRoute;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteAffixGroup;
use App\Models\Season;
use App\Models\User;
use App\Repositories\Interfaces\DungeonRoute\DungeonRouteRepositoryInterface;
use App\Repositories\Interfaces\SeasonRepositoryInterface;
use App\Service\DungeonRoute\Exceptions\SeasonContinuationException;
use App\Service\DungeonRoute\Logging\DungeonRouteSeasonContinuationServiceLoggingInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Override;
use Throwable;

readonly class DungeonRouteSeasonContinuationService implements DungeonRouteSeasonContinuationServiceInterface
{
    public function __construct(
        private SeasonRepositoryInterface                             $seasonRepository,
        private DungeonRouteRepositoryInterface                       $dungeonRouteRepository,
        private DungeonRouteSaveServiceInterface                      $dungeonRouteSaveService,
        private DungeonRouteServiceInterface                          $dungeonRouteService,
        private DungeonRouteSeasonContinuationServiceLoggingInterface $log,
    ) {
    }

    #[Override]
    public function getContinuationSeason(DungeonRoute $dungeonRoute): ?Season
    {
        return $this->getContinuationSeasons(collect([$dungeonRoute]))->get($dungeonRoute->id);
    }

    #[Override]
    public function getContinuationSeasons(Collection $dungeonRoutes): Collection
    {
        $seasonalDungeonRoutes = $dungeonRoutes
            ->filter(static fn(DungeonRoute $dungeonRoute): bool => $dungeonRoute->season_id !== null)
            ->keyBy('id');

        $newestSeasons = $this->seasonRepository->getNewestSeasonsForDungeons(
            $seasonalDungeonRoutes->pluck('dungeon_id')->unique()->values(),
        );
        $routeSeasons = $this->seasonRepository->getSeasonsByIds(
            $seasonalDungeonRoutes->pluck('season_id')->unique()->values(),
        );

        /** @var Collection<int, Season> $result */
        $result = collect();
        foreach ($seasonalDungeonRoutes as $dungeonRoute) {
            $newestSeason = $newestSeasons->get($dungeonRoute->dungeon_id);
            $routeSeason  = $routeSeasons->get($dungeonRoute->season_id);

            if ($newestSeason === null || $routeSeason === null || !$newestSeason->start->greaterThan($routeSeason->start)) {
                continue;
            }

            $result->put($dungeonRoute->id, $newestSeason);
        }

        if ($result->isEmpty()) {
            return $result;
        }

        $clones = $this->dungeonRouteRepository->getClonesOf(
            $seasonalDungeonRoutes->only($result->keys()->all())->pluck('public_key')->values(),
        );

        return $result->reject(static function (Season $season, int $dungeonRouteId) use ($seasonalDungeonRoutes, $clones): bool {
            /** @var DungeonRoute $dungeonRoute */
            $dungeonRoute = $seasonalDungeonRoutes->get($dungeonRouteId);

            return $clones->contains(static fn(DungeonRoute $clone): bool => $clone->clone_of === $dungeonRoute->public_key &&
                $clone->season_id === $season->id &&
                ($clone->author_id === $dungeonRoute->author_id ||
                    ($dungeonRoute->team_id !== null && $clone->team_id === $dungeonRoute->team_id)));
        });
    }

    #[Override]
    public function continueInNewerSeason(DungeonRoute $source): DungeonRoute
    {
        $this->log->continueInNewerSeasonStart($source->id);

        $continuation = null;
        $season       = null;

        try {
            $continuationSeason = $this->getContinuationSeason($source);
            if ($continuationSeason === null) {
                $this->log->continueInNewerSeasonNoContinuationSeason($source->id);

                throw new SeasonContinuationException('This route cannot be continued in a newer season.');
            }

            // Fetched on its own: the affix group defaults walk relations that are not loaded on a season which came
            // out of a collection query
            $season = $this->seasonRepository->findOrFail($continuationSeason->id);

            $continuation = $this->dungeonRouteSaveService->cloneRoute($source);

            try {
                DB::transaction(function () use ($source, $continuation, $season): void {
                    $this->dungeonRouteRepository->update($continuation, [
                        'season_id' => $season->id,
                        'title'     => $source->title,
                    ]);

                    DungeonRouteAffixGroup::query()
                        ->where('dungeon_route_id', $continuation->id)
                        ->whereNotIn('affix_group_id', $season->affixGroups()->select('affix_groups.id'))
                        ->delete();

                    if ($season->affixGroups()->exists()) {
                        $continuation->ensureAffixGroup($season);
                    }

                    /** @var User|null $user */
                    $user = Auth::user();
                    $team = $source->team;
                    if ($user !== null && $team !== null && $team->canAddRemoveRoute($user)) {
                        $team->addRoute($continuation);
                    }
                });

                // Outside the transaction: upgradeMappingVersion() opens its own and drops its own caches
                if ($continuation->mappingVersion?->isLatestForDungeon() === false) {
                    $this->dungeonRouteService->upgradeMappingVersion($continuation);
                }
            } catch (Throwable $throwable) {
                // The copy has already committed; without this the author would be left with a half-continued
                // route that hides the action on the original
                $this->log->continueInNewerSeasonFailed($source->id, $continuation->id);
                $continuation->delete();

                throw $throwable;
            }

            return $continuation->refresh();
        } finally {
            $this->log->continueInNewerSeasonEnd(
                $continuation instanceof DungeonRoute ? $continuation->id : 0,
                $season instanceof Season ? $season->id : 0,
            );
        }
    }
}
