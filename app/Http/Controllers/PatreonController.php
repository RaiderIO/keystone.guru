<?php

namespace App\Http\Controllers;

use App\Service\Patreon\Dtos\LinkToUserIdResult;
use App\Service\Patreon\PatreonApiServiceInterface;
use App\Service\Patreon\PatreonServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;

class PatreonController extends Controller
{
    /**
     * Unlinks the user from Patreon.
     */
    public function unlink(Request $request): RedirectResponse
    {
        // If it was linked, delete it
        optional(Auth::user()->patreonUserLink)->delete();

        Session::flash('status', __('controller.patreon.flash.unlink_successful'));

        return redirect()->route('profile.edit', ['#patreon']);
    }

    /**
     * Checks if the incoming request is a save as new request or not.
     */
    public function link(
        Request                    $request,
        PatreonApiServiceInterface $patreonApiService,
        PatreonServiceInterface    $patreonService
    ): RedirectResponse {
        $state = $request->get('state');
        $code  = $request->get('code');

        // If session was not expired
        if (csrf_token() === $state) {
            // Replace http://localhost:5000/oauth/redirect with your own uri
            $redirectUri = route('patreon.link');

            $linkResult = $patreonService->linkToUserAccount(Auth::user(), $code, $redirectUri);

            match ($linkResult) {
                LinkToUserIdResult::LinkSuccessful => Session::flash('status', __('controller.patreon.flash.link_successful')),
                LinkToUserIdResult::PatreonErrorOccurred => Session::flash('warning', __('controller.patreon.flash.patreon_error_occurred')),
                LinkToUserIdResult::InternalErrorOccurred => Session::flash('warning', __('controller.patreon.flash.internal_error_occurred')),
                LinkToUserIdResult::PatreonSessionExpired => Session::flash('warning', __('controller.patreon.flash.patreon_session_expired')),
            };
        } else {
            Session::flash('warning', __('controller.patreon.flash.session_expired'));
        }

        return redirect()->route('profile.edit', ['#patreon']);
    }

    /**
     * This route is called after a) the user has clicked the link button, b) given the app permission to read their Patron data
     * c) this route is called to give me their info
     */
    public function oauth_redirect($request)
    {

    }
}
