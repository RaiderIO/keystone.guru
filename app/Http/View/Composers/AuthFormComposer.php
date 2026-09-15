<?php

namespace App\Http\View\Composers;

use App\Service\View\RequestViewContextInterface;
use Illuminate\View\View;

readonly class AuthFormComposer implements ViewComposerInterface
{
    public function __construct(
        private RequestViewContextInterface $requestViewContext,
    ) {
    }

    public function compose(View $view): void
    {
        // Logging in or registering from the header modal while already on a guest-only page
        // (/login, /register, the password reset flow, ...) cannot reload that page - the `guest`
        // middleware would bounce the now-authenticated user, and that extra hop ages out the
        // flashed status message. Hand the JS the home page so it gets there in one hop.
        $view->with(
            'authSuccessUrl',
            $this->requestViewContext->isCurrentRouteGuestOnly() ? route('home') : null,
        );
    }
}
