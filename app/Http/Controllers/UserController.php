<?php

namespace App\Http\Controllers;

use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserBenefit;
use App\User;
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
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function makeadmin(Request $request, User $user)
    {
        $currentUser = Auth::user();
        if ($currentUser !== null && array_search($currentUser->name, config('keystoneguru.super_admins', [])) !== false) {
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
     * @param Request $request
     * @param User $user
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

    /**
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     */
    public function delete(Request $request, User $user)
    {
        try {
            $user->delete();
            Session::flash('status', __('controller.user.flash.account_deleted_successfully'));
        } catch (Exception $e) {
            Session::flash('warning', __('controller.user.flash.account_deletion_error'));
        }

        return redirect()->route('admin.users');
    }

    /**
     * @param Request $request
     * @param User $user
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
                    'patreon_user_link_id' => $user->patreonUserLink->id,
                    'patreon_benefit_id'   => $newPatreonBenefitId,
                ]);
            }

            return response()->noContent();
        } else {
            return response(__('controller.user.flash.user_is_not_a_patron'), Http::BAD_REQUEST);
        }
    }
}
