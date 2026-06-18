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
        $regionViewVariables = $this->viewService->getGameServerRegionViewVariables($this->requestViewContext->getUserOrDefaultRegion());
        $view->with('currentExpansion', $regionViewVariables['currentExpansion']);
        $view->with('allAffixGroupsByActiveExpansion', $regionViewVariables['allAffixGroupsByActiveExpansion']);
        $view->with('featuredAffixesByActiveExpansion', $regionViewVariables['featuredAffixesByActiveExpansion']);
        $view->with('currentSeason', $regionViewVariables['currentSeason']);
        $view->with('nextSeason', $regionViewVariables['nextSeason']);
    }
}
