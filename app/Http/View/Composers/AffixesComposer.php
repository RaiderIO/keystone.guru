<?php

namespace App\Http\View\Composers;

use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

readonly class AffixesComposer implements ViewComposerInterface
{
    public function __construct(
        private ViewServiceInterface        $viewService,
        private RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        $gameServerRegion = $this->requestViewContext->getUserOrDefaultRegion();

        $currentSeason = $this->viewService->getCurrentSeasonForRegion($gameServerRegion)->load(['dungeons', 'dungeons.floors']);
        $currentSeason->dungeons->makeHidden(['floors']);
        $view->with('allExpansions', $this->viewService->getAllExpansions()->pluck('id', 'shortname'));
        $view->with('dungeonExpansions', $this->viewService->getDungeonExpansions());
        $view->with('affixes', $this->viewService->getAllAffixes());
        $view->with('currentSeason', $currentSeason);
        $view->with('nextSeason', $this->viewService->getNextSeasonForRegion($gameServerRegion));
        $view->with('allAffixGroups', $this->viewService->getAllAffixGroupsForRegion($gameServerRegion));
        $view->with('expansionsData', $this->viewService->getExpansionsData($gameServerRegion));
        $view->with('currentAffixes', $this->viewService->getAllCurrentAffixesForRegion($gameServerRegion));
    }
}
