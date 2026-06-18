<?php

namespace App\Http\View\Composers;

use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

class HeatmapSearchComposer
{
    public function __construct(
        private readonly ViewServiceInterface        $viewService,
        private readonly RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        $regionViewVariables = $this->viewService->getGameServerRegionViewVariables($this->requestViewContext->getUserOrDefaultRegion());
        $view->with('showAllEnabled', $_COOKIE['dungeon_speedrun_required_npcs_show_all'] ?? '0');
        $view->with('allAffixGroupsByActiveExpansion', $regionViewVariables['allAffixGroupsByActiveExpansion']);
        $view->with('featuredAffixesByActiveExpansion', $regionViewVariables['featuredAffixesByActiveExpansion']);

        $view->with('characterClassSpecializations', $this->viewService->getCharacterClassSpecializations());
        $view->with('characterClasses', $this->viewService->getCharacterClasses());
        $view->with('selectableSpellsByCategory', $this->viewService->getSelectableSpellsByCategory());
        $view->with('allRegions', $this->viewService->getAllRegions());
    }
}
