<?php

namespace App\Http\Controllers;

use App\Models\PatreonData;
use App\Service\Patreon\PatreonApiServiceInterface;
use App\Service\Patreon\PatreonServiceInterface;
use App\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Session;

class PatreonController extends Controller
{

    /**
     * Unlinks the user from Patreon.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function unlink(Request $request)
    {
        // If it was linked, delete it
        Auth::user()->patreondata()->delete();

        Session::flash('status', __('controller.patreon.flash.unlink_successful'));
        return redirect()->route('profile.edit');
    }

    /**
     * Checks if the incoming request is a save as new request or not.
     * @param Request $request
     * @param PatreonApiServiceInterface $patreonApiService
     * @param PatreonServiceInterface $patreonService
     * @return RedirectResponse
     */
    public function link(Request $request, PatreonApiServiceInterface $patreonApiService, PatreonServiceInterface $patreonService)
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
                $user->patreondata()->delete();

                $patreonDataAttributes = [
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
                    $patreonDataAttributes['email'] = 'admin@app.com';
                    $this->createPatreonDataForUser($patreonDataAttributes, $user);
                } else {
                    // Fetch info we need to construct the PatreonData object/be able to link paid benefits
                    $campaignBenefits = $patreonService->loadCampaignBenefits($patreonApiService);
                    $campaignTiers    = $patreonService->loadCampaignTiers($patreonApiService);

                    $identityResponse = $patreonApiService->getIdentity($tokens['access_token']);
                    $member           = collect($identityResponse['included'])->filter(function (array $included) {
                        return $included['type'] === 'member';
                    })->first();

                    $patreonDataAttributes['email'] = $identityResponse['data']['attributes']['email'];
                    $this->createPatreonDataForUser($patreonDataAttributes, $user);

                    // Now that the PatreonData object was created, apply the correct paid benefits to the account
                    $patreonService->applyPaidBenefitsForMember(
                        $campaignBenefits,
                        $campaignTiers,
                        $member
                    );
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

    /**
     * @param array $attributes
     * @param User $user
     * @return PatreonData
     */
    private function createPatreonDataForUser(array $attributes, User $user): PatreonData
    {
        // Create a new PatreonData object and assign it to the user
        $patreonData = PatreonData::create($attributes);
        $user->update([
            'patreon_data_id' => $patreonData->id,
        ]);
        $user->patreondata = $patreonData;

        return $patreonData;
    }

    /**
     * This route is called after a) the user has clicked the link button, b) given the app permission to read their Patron data
     * c) this route is called to give me their info
     *
     * @param $request
     */
    function oauth_redirect($request)
    {

    }
}
