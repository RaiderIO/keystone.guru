<?php

namespace App\Http\View\Composers;

use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

readonly class RollbarComposer implements ViewComposerInterface
{
    public function __construct(
        private ViewServiceInterface $viewService,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with('latestRelease', $this->viewService->getLatestRelease());
    }
}
