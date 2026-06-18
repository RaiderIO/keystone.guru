<?php

namespace App\Http\View\Composers;

use App\Service\MessageBanner\MessageBannerServiceInterface;
use Illuminate\View\View;

class AdminMessageBannerComposer
{
    public function __construct(
        private readonly MessageBannerServiceInterface $messageBannerService,
    ) {
    }

    public function compose(View $view): void
    {
        $view->with('messageBanner', $this->messageBannerService->getMessage());
    }
}
