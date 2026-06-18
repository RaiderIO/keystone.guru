<?php

namespace App\Http\View\Composers;

use App\Models\Season;
use App\Service\Season\SeasonAffixGroupServiceInterface;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

class RouteCoverageAffixGroupComposer
{
    public function __construct(
        private readonly ViewServiceInterface             $viewService,
        private readonly RequestViewContextInterface      $requestViewContext,
        private readonly SeasonAffixGroupServiceInterface $seasonAffixGroupService,
    ) {
    }

    public function compose(View $view): void
    {
        $gameServerRegion = $this->requestViewContext->getUserOrDefaultRegion();
        /** @var Season $currentSeason */
        $currentSeason = $this->viewService->getCurrentSeasonForRegion($gameServerRegion);
        $nextSeason    = $this->viewService->getNextSeasonForRegion($gameServerRegion);

        $selectedSeason         = $currentSeason;
        $cookieSelectedSeasonId = isset($_COOKIE['dungeonroute_coverage_season_id']) ? (int)$_COOKIE['dungeonroute_coverage_season_id'] : 0;
        if ($cookieSelectedSeasonId !== $currentSeason->id &&
            $nextSeason !== null &&
            $cookieSelectedSeasonId === $nextSeason->id) {
            $selectedSeason = $nextSeason;
        }
        $view->with('currentSeason', $currentSeason);
        $view->with('nextSeason', $nextSeason);
        $view->with('selectedSeason', $selectedSeason);
        $view->with('currentAffixGroup', $this->seasonAffixGroupService->getCurrentAffixGroup($selectedSeason));
        $view->with('affixGroups', $selectedSeason->affixGroups);
        $view->with('dungeons', $selectedSeason->dungeons);
    }
}
