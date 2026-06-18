<?php

namespace App\Http\View\Composers;

use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

class DiscoverSearchComposer
{
    public function __construct(
        private readonly ViewServiceInterface        $viewService,
        private readonly RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        $gameServerRegion = $this->requestViewContext->getUserOrDefaultRegion();
        $view->with('currentExpansion', $this->viewService->getCurrentExpansionForRegion($gameServerRegion));
        $view->with('allAffixGroupsByActiveExpansion', $this->viewService->getAllAffixGroupsByActiveExpansion($gameServerRegion));
        $view->with('featuredAffixesByActiveExpansion', $this->viewService->getFeaturedAffixesByActiveExpansion($gameServerRegion));
        $view->with('currentSeason', $this->viewService->getCurrentSeasonForRegion($gameServerRegion));
        $view->with('nextSeason', $this->viewService->getNextSeasonForRegion($gameServerRegion));
    }
}
