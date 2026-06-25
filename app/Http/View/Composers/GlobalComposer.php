<?php

namespace App\Http\View\Composers;

use App\Models\User;
use App\Models\UserReport;
use App\Service\View\RequestViewContextInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;
use Jenssegers\Agent\Agent;

readonly class GlobalComposer implements ViewComposerInterface
{
    public function __construct(
        private ViewServiceInterface        $viewService,
        private RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with('isMobile', new Agent()->isMobile());
        $view->with('isLocal', $this->viewService->isLocal());
        $view->with('isMapping', $this->viewService->isMapping());
        $view->with('isProduction', $this->viewService->isProduction());

        // Don't include the viewName in the layouts - they must inherit from whatever calls it!
        if (!str_starts_with((string)$view->getName(), 'layouts')) {
            $view->with('viewName', $view->getName());
        } elseif (!isset($view->getData()['viewName'])) {
            $view->with('viewName', 'home');
        }
        $view->with('theme', $_COOKIE['theme'] ?? User::DEFAULT_THEME);
        $view->with('isUserAdmin', $this->requestViewContext->isUserAdmin());
        $view->with('adFree', $this->requestViewContext->isAdFree());
        $view->with('userOrDefaultRegion', $this->requestViewContext->getUserOrDefaultRegion());
        $view->with('currentUserGameVersion', $this->requestViewContext->getCurrentUserGameVersion());
        $view->with('numUserReports', $this->requestViewContext->isUserAdmin() ? UserReport::where('status', 0)->count() : 2);
    }
}
