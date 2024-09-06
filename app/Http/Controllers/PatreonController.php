<?php

namespace App\Http\Controllers;

use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
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
    public function link(Request $request, PatreonApiServiceInterface $patreonApiService, PatreonServiceInterface $patreonService): RedirectResponse
    {
        $state = $request->get('state');
        $code  = $request->get('code');

        // If session was not expired
        if (csrf_token() === $state) {
            // Replace http://localhost:5000/oauth/redirect with your own uri
            $redirect_uri = route('patreon.link');

            $tokens = $patreonApiService->getAccessTokenFromCode($code, $redirect_uri);
            if (!isset($tokens['error'])) {
                $user = Auth::user();

                // Save new tokens to database
                // Delete existing patreon data, if any
                $user->patreonUserLink?->delete();

                $patreonUserLinkAttributes = [
                    'user_id'       => $user->id,
                    'scope'         => $tokens['scope'],
                    'access_token'  => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'version'       => $tokens['version'],
                    'expires_at'    => date('Y-m-d H:i:s', time() + $tokens['expires_in']),
                ];

                // Special case for the admin user - since the service needs this account to exist we need to just create
                // the PatreonData for this user and ignore the paid benefits (admins get everything, anyways)
                if ($user->id === 1) {
                    $patreonUserLinkAttributes['email'] = 'admin@app.com';
                    $this->createPatreonUserLink($patreonUserLinkAttributes, $user);
                } else {
                    // Fetch info we need to construct the PatreonData object/be able to link paid benefits
                    $campaignBenefits = $patreonService->loadCampaignBenefits($patreonApiService);
                    $campaignTiers    = $patreonService->loadCampaignTiers($patreonApiService);

                    $identityResponse = $patreonApiService->getIdentity($tokens['access_token']);
                    if (isset($identityResponse['errors'])) {
                        Session::flash('warning', __('controller.patreon.flash.patreon_error_occurred'));
                    } else if (!isset($identityResponse['included'])) {
                        Session::flash('warning', __('controller.patreon.flash.internal_error_occurred'));
                    } else {
                        $member = collect($identityResponse['included'])->filter(static fn(array $included) => $included['type'] === 'member')->first();

                        $patreonUserLinkAttributes['email'] = $identityResponse['data']['attributes']['email'];
                        $this->createPatreonUserLink($patreonUserLinkAttributes, $user);

                        // Now that the PatreonData object was created, apply the correct paid benefits to the account
                        $patreonService->applyPaidBenefitsForMember(
                            $campaignBenefits,
                            $campaignTiers,
                            $member
                        );
                    }
                }

                // Message to the user
                Session::flash('status', __('controller.patreon.flash.link_successful'));
            } else {
                Session::flash('warning', __('controller.patreon.flash.patreon_session_expired'));
            }
        } else {
            Session::flash('warning', __('controller.patreon.flash.session_expired'));
        }

        return redirect()->route('profile.edit', ['#patreon']);
    }

    private function createPatreonUserLink(array $attributes, User $user): PatreonUserLink
    {
        $existingPatreonUserLink = PatreonUserLink::where('email', $attributes['email'])->first();

        // If the link already exists, remove it entirely. Can't couple the same Patreon account to 2 Keystone.guru accounts
        if ($existingPatreonUserLink !== null) {
            $existingPatreonUserLink->user()->update(['patreon_user_link_id' => null]);

            $existingPatreonUserLink->delete();
        }

        // Create a new PatreonData object and assign it to the user
        $patreonUserLink = PatreonUserLink::create($attributes);
        $user->update([
            'patreon_user_link_id' => $patreonUserLink->id,
        ]);
        $user->patreonUserLink = $patreonUserLink;

        return $patreonUserLink;
    }

    /**
     * This route is called after a) the user has clicked the link button, b) given the app permission to read their Patron data
     * c) this route is called to give me their info
     */
    public function oauth_redirect($request)
    {

    }
}
