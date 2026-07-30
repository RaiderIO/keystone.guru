<?php

namespace App\Http\View\Composers;

use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

readonly class DungeonGridTabsComposer implements ViewComposerInterface
{
    public function __construct(
        private ViewServiceInterface        $viewService,
        private RequestViewContextInterface $requestViewContext,
        private Request                     $request,
    ) {
    }

    public function compose(View $view): void
    {
        $gameServerRegion = $this->requestViewContext->getUserOrDefaultRegion();
        $view->with('activeExpansions', $this->viewService->getActiveExpansions());
        $view->with('currentSeason', $this->viewService->getCurrentSeasonForRegion($gameServerRegion));
        $view->with('nextSeason', $this->viewService->getNextSeasonForRegion($gameServerRegion));

        // Which season's tab to open on - the current season unless another one was explicitly asked for,
        // for example through the header's "next season" card (#3761). Garbage input casts to 0 = none.
        $view->with('requestedSeasonId', $this->request->integer('season') ?: null);
    }
}
