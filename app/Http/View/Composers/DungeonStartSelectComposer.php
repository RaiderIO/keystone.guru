<?php

namespace App\Http\View\Composers;

use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

readonly class DungeonStartSelectComposer implements ViewComposerInterface
{
    public function __construct(
        private readonly ViewServiceInterface $viewService,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with('dungeonStartsByDungeonId', $this->viewService->getDungeonStartsByDungeonId());
    }
}
