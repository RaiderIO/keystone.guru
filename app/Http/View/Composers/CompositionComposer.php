<?php

namespace App\Http\View\Composers;

use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

class CompositionComposer
{
    public function __construct(
        private readonly ViewServiceInterface $viewService,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with('specializations', $this->viewService->getCharacterClassSpecializations());
        $view->with('classes', $this->viewService->getCharacterClasses());
        $view->with('racesClasses', $this->viewService->getCharacterRacesClasses());
        $view->with('allFactions', $this->viewService->getAllFactions());
    }
}
