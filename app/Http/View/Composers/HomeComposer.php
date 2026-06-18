<?php

namespace App\Http\View\Composers;

use App\Models\GameVersion\GameVersion;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

class HomeComposer
{
    public function __construct(
        private readonly ViewServiceInterface        $viewService,
        private readonly RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with('userCount', $this->viewService->getUserCount());
        $view->with('demoRouteDungeons', $this->viewService->getDemoRouteDungeons());
        $view->with('demoRouteMapping', $this->viewService->getDemoRouteMapping());

        $regionViewVariables = $this->viewService->getGameServerRegionViewVariables($this->requestViewContext->getUserOrDefaultRegion());
        $view->with('currentSeason', $regionViewVariables['currentSeason']);
        $view->with('defaultGameVersion', GameVersion::getDefaultGameVersion());
    }
}
