<?php

namespace App\Http\View\Composers;

use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

class RouteAttributesComposer
{
    public function __construct(
        private readonly ViewServiceInterface $viewService,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with('allRouteAttributes', $this->viewService->getAllRouteAttributes());
    }
}
