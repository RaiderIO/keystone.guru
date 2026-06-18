<?php

namespace App\Http\View\Composers;

use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

class AffixesComposer
{
    public function __construct(
        private readonly ViewServiceInterface        $viewService,
        private readonly RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        $gameServerRegion = $this->requestViewContext->getUserOrDefaultRegion();
        $view->with('allExpansions', $this->viewService->getAllExpansions()->pluck('id', 'shortname'));
        $view->with('dungeonExpansions', $this->viewService->getDungeonExpansions());
        $view->with('affixes', $this->viewService->getAllAffixes());
        $view->with('currentSeason', $this->viewService->getCurrentSeasonForRegion($gameServerRegion));
        $view->with('nextSeason', $this->viewService->getNextSeasonForRegion($gameServerRegion));
        $view->with('allAffixGroups', $this->viewService->getAllAffixGroupsForRegion($gameServerRegion));
        $view->with('expansionsData', $this->viewService->getExpansionsData($gameServerRegion));
        $view->with('currentAffixes', $this->viewService->getAllCurrentAffixesForRegion($gameServerRegion));
    }
}
