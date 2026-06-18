<?php

namespace App\Http\View\Composers;

use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

class ChangelogFlagComposer
{
    public function __construct(
        private readonly ViewServiceInterface $viewService,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with(
            'hasNewChangelog',
            isset($_COOKIE['changelog_release']) && $this->viewService->getLatestRelease()->id > (int)$_COOKIE['changelog_release'],
        );
    }
}
