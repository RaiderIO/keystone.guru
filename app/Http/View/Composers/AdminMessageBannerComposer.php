<?php

namespace App\Http\View\Composers;

use App\Service\MessageBanner\MessageBannerServiceInterface;
use Illuminate\View\View;

readonly class AdminMessageBannerComposer implements ViewComposerInterface
{
    public function __construct(
        private MessageBannerServiceInterface $messageBannerService,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with('messageBanner', $this->messageBannerService->getMessage());
    }
}
