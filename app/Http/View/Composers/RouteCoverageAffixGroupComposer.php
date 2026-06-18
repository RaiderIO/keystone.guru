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
        $regionViewVariables = $this->viewService->getGameServerRegionViewVariables($this->requestViewContext->getUserOrDefaultRegion());
        /** @var Season $selectedSeason */
        $selectedSeason         = $regionViewVariables['currentSeason'];
        $cookieSelectedSeasonId = isset($_COOKIE['dungeonroute_coverage_season_id']) ? (int)$_COOKIE['dungeonroute_coverage_season_id'] : 0;
        if ($cookieSelectedSeasonId !== $regionViewVariables['currentSeason']->id &&
            $regionViewVariables['nextSeason'] !== null &&
            $cookieSelectedSeasonId === $regionViewVariables['nextSeason']->id) {
            $selectedSeason = $regionViewVariables['nextSeason'];
        }
        $view->with('currentSeason', $regionViewVariables['currentSeason']);
        $view->with('nextSeason', $regionViewVariables['nextSeason']);
        $view->with('selectedSeason', $selectedSeason);
        $view->with('currentAffixGroup', $this->seasonAffixGroupService->getCurrentAffixGroup($selectedSeason));
        $view->with('affixGroups', $selectedSeason->affixGroups);
        $view->with('dungeons', $selectedSeason->dungeons);
    }
}
