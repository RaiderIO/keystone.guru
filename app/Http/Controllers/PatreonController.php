<?php

namespace App\Http\Controllers;

use App\Models\PatreonData;
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
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function unlink(Request $request)
    {
        $user = Auth::user();
        if ($user !== null) {
            // If it was linked, delete it
            if ($user->patreondata !== null) {
                $user->patreondata->delete();
            }

            $result = redirect()->route('profile.edit');
            Session::flash('status', 'Your Patreon account has successfully been unlinked,');
        } else {
            Session::flash('warning', 'You need to be logged in to view this page.');
            $result = redirect()->route('home');
        }

        return $result;
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

                // Fetch info we need to construct the PatreonData object/be able to link paid benefits
                $campaignBenefits = $patreonService->loadCampaignBenefits($patreonApiService);
                $campaignTiers    = $patreonService->loadCampaignTiers($patreonApiService);

                $identityResponse = $patreonApiService->getIdentity($tokens['access_token']);
                $member           = collect($identityResponse['included'])->filter(function (array $included) {
                    return $included['type'] === 'member';
                })->first();

                // Create a new PatreonData object and assign it to the user
                $patreonData = PatreonData::create([
                    'user_id'       => $user->id,
                    'email'         => $identityResponse['data']['attributes']['email'],
                    'scope'         => $tokens['scope'],
                    'access_token'  => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'version'       => $tokens['version'],
                    'expires_at'    => date('Y-m-d H:i:s', time() + $tokens['expires_in']),
                ]);
                $user->update([
                    'patreon_data_id' => $patreonData->id,
                ]);
                $user->patreondata = $patreonData;

                // Now that the PatreonData object was created, apply the correct paid benefits to the account
                $patreonService->applyPaidBenefitsForMember(
                    $campaignBenefits,
                    $campaignTiers,
                    $member
                );

                // Message to the user
                Session::flash('status', 'Your Patreon has been linked successfully. Thank you!');
            } else {
                Session::flash('warning', 'Your Patreon session has expired. Please try again.');
            }
        } else {
            Session::flash('warning', 'Your session has expired. Please try again.');
        }

        return redirect()->route('profile.edit', ['#patreon']);
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
