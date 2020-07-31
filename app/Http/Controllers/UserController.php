<?php

namespace App\Http\Controllers;

use App\Models\PaidTier;
use App\Models\PatreonTier;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class UserController extends Controller
{
    /**
     * Handles the viewing of a collection of items in a table.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\
     */
    public function list()
    {
        return view('admin.user.list', [
            'models'    => User::with('roles', 'dungeonroutes', 'patreondata')->get(),
            'paidTiers' => PaidTier::all()
        ]);
    }

    /**
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function makeadmin(Request $request, User $user)
    {
        $currentUser = Auth::user();
        if ($currentUser !== null && $currentUser->name === 'Admin') {
            if (!$user->hasRole('admin')) {
                $user->attachRole('admin');

                // Message to the user
                \Session::flash('status', sprintf(__('User %s is now an admin'), $user->name));
            } else {
                $user->detachRole('admin');

                // Message to the user
                \Session::flash('status', sprintf(__('User %s is no longer an admin'), $user->name));
            }
        }

        return redirect()->route('admin.users');
    }

    /**
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function makeuser(Request $request, User $user)
    {
        $currentUser = Auth::user();
        if ($currentUser !== null && $currentUser->name === 'Admin') {
            $user->detachRoles($user->roles);

            $user->attachRole('user');

            // Message to the user
            \Session::flash('status', sprintf(__('User %s is now a user'), $user->name));
        }

        return redirect()->route('admin.users');
    }

    /**
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function delete(Request $request, User $user)
    {
        try {
            $user->delete();
            \Session::flash('status', __('Account deleted successfully.'));
        } catch (\Exception $e) {
            \Session::flash('warning', __('An error occurred. Please try again.'));
        }

        return redirect()->route('admin.users');
    }

    /**
     * @param Request $request
     * @param User $user
     */
    public function storepaidtiers(Request $request, User $user)
    {
        $newPaidTierIds = $request->get('paidtiers', []);

        if (isset($user->patreondata)) {
            // Remove old paid tiers
            $user->patreondata->tiers()->delete();

            foreach ($newPaidTierIds as $newPaidTierId) {
                $newPaidTier = new PatreonTier([
                    'patreon_data_id' => $user->patreondata->id,
                    'paid_tier_id'    => $newPaidTierId
                ]);
                $newPaidTier->save();
            }

            return response()->noContent();
        } else {
            return response('This user is not a Patron', Http::BAD_REQUEST);
        }
    }
}
