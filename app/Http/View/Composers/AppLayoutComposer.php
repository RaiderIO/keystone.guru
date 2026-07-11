<?php

namespace App\Http\View\Composers;

use App\Service\MessageBanner\MessageBannerServiceInterface;
use App\Service\ReadOnlyMode\ReadOnlyModeServiceInterface;
use App\Service\View\ViewServiceInterface;
use Illuminate\View\View;

readonly class AppLayoutComposer implements ViewComposerInterface
{
    public function __construct(
        private ViewServiceInterface          $viewService,
        private MessageBannerServiceInterface $messageBannerService,
        private ReadOnlyModeServiceInterface  $readOnlyModeService,
    ) {
    }

    public function compose(View $view): void
    {
        $appVersionInfo = $this->viewService->getAppVersionInfo();
        $view->with('version', $appVersionInfo['version']);
        $view->with('revision', $appVersionInfo['revision']);
        $view->with('nameAndVersion', $appVersionInfo['nameAndVersion']);
        $view->with('messageBanner', $this->messageBannerService->getMessage());
        $view->with('readOnlyEnabled', $this->readOnlyModeService->isReadOnly());
        $view->with('worktree', config('keystoneguru.worktree'));
    }
}
