<?php

namespace App\Http\View\Composers;

use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

class ProfileEditComposer
{
    public function __construct(
        private readonly ViewServiceInterface $viewService,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with('allClasses', $this->viewService->getCharacterClasses());
        $view->with('allRegions', $this->viewService->getAllRegions());
    }
}
