<?php

namespace App\Http\Controllers;

use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Session;
use Teapot\StatusCode\Http;

class UserController extends Controller
{
    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return Factory
     */
    public function list()
    {
        return view('admin.user.list', [
            'patreonBenefits' => PatreonBenefit::all(),
        ]);
    }

    /**
     * @return RedirectResponse
     */
    public function makeadmin(Request $request, User $user)
    {
        $currentUser = Auth::user();
        if ($currentUser !== null && array_search($currentUser->name, config('keystoneguru.super_admins', []), true) !== false) {
            if (!$user->hasRole('admin')) {
                $user->attachRole('admin');

                // Message to the user
                Session::flash('status', sprintf(__('controller.user.flash.user_is_now_an_admin'), $user->name));
            } else {
                $user->detachRole('admin');

                // Message to the user
                Session::flash('status', sprintf(__('controller.user.flash.user_is_no_longer_an_admin'), $user->name));
            }
        }

        return redirect()->route('admin.users');
    }

    /**
     * @return RedirectResponse
     */
    public function makeuser(Request $request, User $user)
    {
        $currentUser = Auth::user();
        if ($currentUser !== null && $currentUser->name === 'Admin') {
            $user->detachRoles($user->roles);

            $user->attachRole('user');

            // Message to the user
            Session::flash('status', sprintf(__('controller.user.flash.user_is_now_a_user'), $user->name));
        }

        return redirect()->route('admin.users');
    }

    public function delete(Request $request, User $user): RedirectResponse
    {
        try {
            $user->delete();
            Session::flash('status', __('controller.user.flash.account_deleted_successfully'));
        } catch (Exception) {
            Session::flash('warning', __('controller.user.flash.account_deletion_error'));
        }

        return redirect()->route('admin.users');
    }

    public function grantAllBenefits(Request $request, User $user): RedirectResponse
    {
        try {
            if (isset($user->patreonUserLink)) {
                // Remove old patreon benefits
                $user->patreonUserLink->patreonuserbenefits()->delete();
            } else {
                // Create a dummy patreon link
                $patreonUserLink = PatreonUserLink::create([
                    'user_id'       => $user->id,
                    'email'         => $user->email,
                    'scope'         => 'identity identity[email] identity.memberships campaigns',
                    'access_token'  => PatreonUserLink::PERMANENT_TOKEN,
                    'refresh_token' => PatreonUserLink::PERMANENT_TOKEN,
                    'version'       => '0.0.1',
                    'expires_at'    => Carbon::now()->addYears(100),
                ]);
                $user->setRelation('patreonUserLink', $patreonUserLink);

                $user->update(['patreon_user_link_id' => $patreonUserLink->id]);
            }

            // Grand them all benefits
            foreach (PatreonBenefit::ALL as $patreonBenefit => $patreonBenefitId) {
                PatreonUserBenefit::create([
                    'patreon_user_link_id' => $user->patreon_user_link_id,
                    'patreon_benefit_id'   => $patreonBenefitId,
                ]);
            }

            Session::flash('status', __('controller.user.flash.all_benefits_granted_successfully'));
        } catch (Exception) {
            Session::flash('warning', __('controller.user.flash.error_granting_all_benefits'));
        }

        return redirect()->route('admin.users');
    }

    /**
     * @return Application|ResponseFactory|Response
     */
    public function storePatreonBenefits(Request $request, User $user)
    {
        $newPatreonBenefitIds = $request->get('patreonBenefits', []);

        if (isset($user->patreonUserLink)) {
            // Remove old patreon benefits
            $user->patreonUserLink->patreonuserbenefits()->delete();

            foreach ($newPatreonBenefitIds as $newPatreonBenefitId) {
                PatreonUserBenefit::create([
                    'patreon_user_link_id' => $user->patreon_user_link_id,
                    'patreon_benefit_id'   => $newPatreonBenefitId,
                ]);
            }

            return response()->noContent();
        } else {
            return response(__('controller.user.flash.user_is_not_a_patron'), Http::BAD_REQUEST);
        }
    }
}
