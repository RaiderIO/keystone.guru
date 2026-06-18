<?php

namespace App\Http\View\Composers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TeamSelectComposer
{
    public function compose(View $view): void
    {
        $view->with('teams', Auth::check() ? Auth::user()->teams : collect());
    }
}
