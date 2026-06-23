<?php

namespace App\Http\View\Composers;

use App\Models\GameVersion\GameVersion;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

readonly class HomeComposer implements ViewComposerInterface
{
    public function __construct(
        private ViewServiceInterface        $viewService,
        private RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with('userCount', $this->viewService->getUserCount());
        $view->with('demoRoutes', $this->viewService->getDemoRoutes());
        $view->with('demoRouteDungeons', $this->viewService->getDemoRouteDungeons());
        $view->with('demoRouteMapping', $this->viewService->getDemoRouteMapping());

        $view->with('currentSeason', $this->viewService->getCurrentSeasonForRegion($this->requestViewContext->getUserOrDefaultRegion()));
        $view->with('defaultGameVersion', GameVersion::getDefaultGameVersion());
    }
}
