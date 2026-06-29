<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;

interface ViewComposerInterface
{
    public function compose(View $view): void;
}
