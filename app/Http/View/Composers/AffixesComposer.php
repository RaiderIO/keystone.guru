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
        $regionViewVariables = $this->viewService->getGameServerRegionViewVariables($this->requestViewContext->getUserOrDefaultRegion());
        $view->with('allExpansions', $this->viewService->getAllExpansions()->pluck('id', 'shortname'));
        $view->with('dungeonExpansions', $this->viewService->getDungeonExpansions());
        $view->with('affixes', $this->viewService->getAllAffixes());
        $view->with('currentSeason', $regionViewVariables['currentSeason']);
        $view->with('nextSeason', $regionViewVariables['nextSeason']);
        $view->with('allAffixGroups', $regionViewVariables['allAffixGroups']);
        $view->with('expansionsData', $regionViewVariables['expansionsData']);
        $view->with('currentAffixes', $regionViewVariables['allCurrentAffixes']);
    }
}
