<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Models\Patreon\PatreonBenefit;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AjaxProfileController extends Controller
{
    public function legalAgree(): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $user->update([
            'legal_agreed' => 1,
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

    /**
     * @throws AuthorizationException
     */
    public function removeAdFreeGiveaway(Request $request, User $user): Response
    {
        if ($user->patreonAdFreeGiveaway === null) {
            abort(422, __('controller.profile.error.remove_ad_free_giveaway_not_found'));
        }

        // Only the giver may take their own giveaway back
        Gate::authorize('revokeAdFreeGiveaway', $user);

        $user->patreonAdFreeGiveaway->delete();

        return response()->noContent();
    }
}
