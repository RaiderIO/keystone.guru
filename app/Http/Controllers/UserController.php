<?php

namespace App\Http\Controllers;

use App\Models\Laratrust\Role;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Patreon\PatreonUserBenefit;
use App\Models\Patreon\PatreonUserLink;
use App\Models\User;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Session;
use Teapot\StatusCode\Http;

class UserController extends Controller
{
    /**
     * Handles the viewing of a collection of items in a table.
     */
    public function get(): View
    {
        return view('admin.user.list', [
            'allPatreonBenefits' => PatreonBenefit::all(),
            'allRoles'           => Role::all(),
        ]);
    }

    public function makeRole(Request $request, User $user, string $role): RedirectResponse
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($currentUser !== null) {
            if ($role === Role::ROLE_ADMIN) {
                // Only super admins can make someone else admin!
                if (in_array($currentUser->name, config('keystoneguru.super_admins', []), true)) {
                    if (!$user->hasRole(Role::ROLE_ADMIN)) {
                        $user->addRole(Role::ROLE_ADMIN);

                        // Message to the user
                        Session::flash('status', __('controller.user.flash.user_is_now_an_admin', ['user' => $user->name]));
                    } else {
                        $user->removeRole(Role::ROLE_ADMIN);

                        // Message to the user
                        Session::flash('status', __('controller.user.flash.user_is_no_longer_an_admin', ['user' => $user->name]));
                    }
                }
            } elseif ($role === Role::ROLE_USER) {
                $user->removeRoles($user->roles->toArray());

                $user->addRole($role);

                // Message to the user
                Session::flash('status', __('controller.user.flash.user_is_now_a_user', ['user' => $user->name]));
            } else {
                $user->addRole($role);

                // Message to the user
                Session::flash('status', __('controller.user.flash.user_is_now_a_role', [
                    'user' => $user->name,
                    'role' => $role,
                ]));
            }
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
                $user->patreonUserLink->patreonUserBenefits()->delete();
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
    public function storePatreonBenefits(Request $request, User $user): Response
    {
        $newPatreonBenefitIds = $request->get('patreonBenefits', []);

        if (isset($user->patreonUserLink)) {
            // Remove old patreon benefits
            $user->patreonUserLink->patreonUserBenefits()->delete();

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
