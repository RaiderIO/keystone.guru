<?php

namespace App\Http\Controllers;

use App\Models\PaidTier;
use App\Models\PatreonData;
use App\Models\PatreonTier;
use Art4\JsonApiClient\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Patreon\API;
use Patreon\OAuth;

class PatreonController extends Controller
{

    /**
     * Unlinks the user from Patreon.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
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
            \Session::flash('status', 'Your Patreon account has successfully been unlinked,');
        } else {
            \Session::flash('warning', 'You need to be logged in to view this page.');
            $result = redirect()->route('home');
        }

        return $result;
    }

    /**
     * Checks if the incoming request is a save as new request or not.
     * @param Request $request
     * @return bool
     */
    public function link(Request $request)
    {
        $state = $request->get('state');
        $code = $request->get('code');

        // If session was not expired
        if (csrf_token() === $state) {
            $client_id = env('PATREON_CLIENT_ID');
            $client_secret = env('PATREON_CLIENT_SECRET');

            $oauth_client = new OAuth($client_id, $client_secret);

            // Replace http://localhost:5000/oauth/redirect with your own uri
            $redirect_uri = route('patreon.link');

            /*
             * Make sure that you're using this snippet as Step 2 of the OAuth flow: https://www.patreon.com/platform/documentation/oauth
             * so that you have the 'code' query parameter.
             */
            $tokens = $oauth_client->get_tokens($code, $redirect_uri);
            if (!isset($tokens['error'])) {
                // Save new tokens to database
                // Delete existing, if any
                $userId = Auth::user()->id;
                PatreonData::where('user_id', $userId)->delete();

                $patreonData = new PatreonData();
                $patreonData->user_id = $userId;
                $patreonData->access_token = $tokens['access_token'];
                $patreonData->refresh_token = $tokens['refresh_token'];
                $patreonData->expires_at = date("Y-m-d H:i:s", time() + $tokens['expires_in']);

                $patreonData->save();

                $api_client = new API($patreonData->access_token);
                /** @var Document $patron_response */
                $patronResponse = $api_client->fetch_user();
                $responseArray = $patronResponse->asArray(true);
                /**
                 * array:2 [▼
                 * "data" => array:4 [▼
                 * "type" => "user"
                 * "id" => "13821632"
                 * "attributes" => array:25 [▼
                 * "about" => "Hi! I'm a software developer from the Netherlands. I'm the author of a website called https://keystone.guru, if you're a World of Warcraft player you may want to check it out! ◀"
                 * "can_see_nsfw" => null
                 * "created" => "2018-09-27T14:40:22+00:00"
                 * "default_country_code" => null
                 * "discord_id" => null
                 * "email" => "patreon.com@clearbits.nl"
                 * "facebook" => null
                 * "facebook_id" => null
                 * "first_name" => "Wotuu"
                 * "full_name" => "Wotuu"
                 * "gender" => 0
                 * "has_password" => true
                 * "image_url" => "https://c10.patreonusercontent.com/3/eyJ3IjoyMDB9/patreon-media/p/user/13821632/ba4bc8404bdd455b8f48731ac1429781/1?token-time=2145916800&token-hash=6rniANTtsOu1 ▶"
                 * "is_deleted" => false
                 * "is_email_verified" => true
                 * "is_nuked" => false
                 * "is_suspended" => false
                 * "last_name" => ""
                 * "social_connections" => array:8 [▶]
                 * "thumb_url" => "https://c10.patreonusercontent.com/3/eyJoIjoxMDAsInciOjEwMH0%3D/patreon-media/p/user/13821632/ba4bc8404bdd455b8f48731ac1429781/1?token-time=2145916800&token-has ▶"
                 * "twitch" => null
                 * "twitter" => null
                 * "url" => "https://www.patreon.com/keystoneguru"
                 * "vanity" => "keystoneguru"
                 * "youtube" => null
                 * ]
                 * "relationships" => array:1 [▼
                 * "pledges" => array:1 [▼
                 * "data" => []
                 * ]
                 * ]
                 * ]
                 * "links" => array:1 [▼
                 * "self" => "https://www.patreon.com/api/user/13821632"
                 * ]
                 * ]
                 */
                // I pray this works. I have no reason to believe this will work
                $pledgeData = $responseArray['data']['relationships']['pledges']['data'];
                foreach ($pledgeData as $key => $item) {
                    /** @var PaidTier $paidTier */
                    $paidTier = PaidTier::where('name', $key)->first();

                    // If the tier is found..
                    if ($paidTier !== null) {
                        // Save it in the database; the user now has access to that tier!
                        $patreonTier = new PatreonTier();
                        $patreonTier->paid_tier_id = $paidTier->id;
                        $patreonTier->patreon_data_id = $patreonData->id;
                        $patreonTier->save();
                    }
                }


                /*
                 $patron will have the authenticated user's user data, and
                 $pledge will have their patronage data.
                 Typically, you will save the relevant pieces of this data to your database,
                 linked with their user account on your site,
                 so your site can customize its experience based on their Patreon data.
                 You will also want to save their $access_token and $refresh_token to your database,
                 linked to their user account on your site,
                 so that you can refresh their Patreon data on your own schedule.
                 */

                // Message to the user
                \Session::flash('status', 'Your Patreon has been linked successfully. Thank you!');
            } else {
                \Session::flash('warning', 'Your Patreon session has expired. Please try again.');
            }
        } else {
            \Session::flash('warning', 'Your session has expired. Please try again.');
        }

        return redirect()->route('profile.edit');
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
