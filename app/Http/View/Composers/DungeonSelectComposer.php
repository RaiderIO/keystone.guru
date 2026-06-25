<?php

namespace App\Http\View\Composers;

use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

readonly class DungeonSelectComposer implements ViewComposerInterface
{
    public function __construct(
        private ViewServiceInterface        $viewService,
        private RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        $gameServerRegion = $this->requestViewContext->getUserOrDefaultRegion();
        $view->with('currentSeason', $this->viewService->getCurrentSeasonForRegion($gameServerRegion));
        $view->with('nextSeason', $this->viewService->getNextSeasonForRegion($gameServerRegion));
        $view->with('allExpansions', $this->viewService->getAllExpansions());
        $view->with('allDungeons', $this->viewService->getDungeonsByExpansionIdDesc());
        $view->with('allRaids', $this->viewService->getRaidsByExpansionIdDesc());
        $view->with('allActiveDungeons', $this->viewService->getActiveDungeonsByExpansionIdDesc());
        $view->with('allActiveRaids', $this->viewService->getActiveRaidsByExpansionIdDesc());
        $view->with('siegeOfBoralus', $this->viewService->getSiegeOfBoralus());
    }
}
