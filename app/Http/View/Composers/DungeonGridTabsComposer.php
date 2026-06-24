<?php

namespace App\Http\View\Composers;

use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

readonly class DungeonGridTabsComposer implements ViewComposerInterface
{
    public function __construct(
        private ViewServiceInterface        $viewService,
        private RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        $gameServerRegion = $this->requestViewContext->getUserOrDefaultRegion();
        $view->with('activeExpansions', $this->viewService->getActiveExpansions());
        $view->with('currentSeason', $this->viewService->getCurrentSeasonForRegion($gameServerRegion));
        $view->with('nextSeason', $this->viewService->getNextSeasonForRegion($gameServerRegion));
    }
}
