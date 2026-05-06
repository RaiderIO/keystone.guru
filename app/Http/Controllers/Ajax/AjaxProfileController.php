<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Models\Patreon\PatreonBenefit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AjaxProfileController extends Controller
{
    public function legalAgree(Request $request): Response
    {
        $time = $request->get('time', -1);

        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'legal_agreed'    => 1,
            'legal_agreed_ms' => $time,
        ]);

        return response()->noContent();
    }

    public function addAdFreeGiveaway(Request $request, User $user): PatreonAdFreeGiveaway
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (PatreonAdFreeGiveaway::getCountLeft($currentUser) <= 0) {
            abort(422, __('controller.profile.error.add_ad_free_giveaway_limit_reached'));
        }

        if ($user->hasPatreonBenefit(PatreonBenefit::AD_FREE)) {
            abort(422, __('controller.profile.error.add_ad_free_giveaway_already_ad_free'));
        }

        if ($user->hasAdFreeGiveaway()) {
            abort(422, __('controller.profile.error.add_ad_free_giveaway_already_has_giveaway'));
        }

        return PatreonAdFreeGiveaway::create([
            'giver_user_id'    => $currentUser->id,
            'receiver_user_id' => $user->id,
        ]);
    }

    public function removeAdFreeGiveaway(Request $request, User $user): Response
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($user->patreonAdFreeGiveaway === null) {
            abort(422, __('controller.profile.error.remove_ad_free_giveaway_not_found'));
        }

        if ($user->patreonAdFreeGiveaway->giver_user_id !== $currentUser->id) {
            abort(422, __('controller.profile.error.remove_ad_free_giveaway_not_yours'));
        }

        $user->patreonAdFreeGiveaway->delete();

        return response()->noContent();
    }
}
