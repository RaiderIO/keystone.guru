<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;

class ProfileNewRouteStyleComposer
{
    public function compose(View $view): void
    {
        $view->with('newRouteStyle', $_COOKIE['route_coverage_new_route_style'] ?? 'search');
    }
}
