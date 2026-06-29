<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;

readonly class PullsComposer implements ViewComposerInterface
{
    public function compose(View $view): void
    {
        $view->with('showAllEnabled', $_COOKIE['dungeon_speedrun_required_npcs_show_all'] ?? '0');
    }
}
