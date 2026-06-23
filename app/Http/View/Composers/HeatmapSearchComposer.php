<?php

namespace App\Http\View\Composers;

use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

readonly class HeatmapSearchComposer implements ViewComposerInterface
{
    public function __construct(
        private ViewServiceInterface        $viewService,
        private RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        $gameServerRegion = $this->requestViewContext->getUserOrDefaultRegion();
        $view->with('showAllEnabled', $_COOKIE['dungeon_speedrun_required_npcs_show_all'] ?? '0');
        $view->with('allAffixGroupsByActiveExpansion', $this->viewService->getAllAffixGroupsByActiveExpansion($gameServerRegion));
        $view->with('featuredAffixesByActiveExpansion', $this->viewService->getFeaturedAffixesByActiveExpansion($gameServerRegion));

        $view->with('characterClassSpecializations', $this->viewService->getCharacterClassSpecializations());
        $view->with('characterClasses', $this->viewService->getCharacterClasses());
        $view->with('selectableSpellsByCategory', $this->viewService->getSelectableSpellsByCategory());
        $view->with('allRegions', $this->viewService->getAllRegions());
    }
}
