<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;

class PullsComposer
{
    public function compose(View $view): void
    {
        $view->with('showAllEnabled', $_COOKIE['dungeon_speedrun_required_npcs_show_all'] ?? '0');
    }
}
