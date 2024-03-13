<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\TeamDefaultRoleFormRequest;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Patreon\PatreonAdFreeGiveaway;
use App\Models\Patreon\PatreonBenefit;
use App\Models\Team;
use App\Models\TeamUser;
use App\Models\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class AjaxTeamController extends Controller
{
    public function get(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        return $user->teams;
    }

    /**
     * @throws AuthorizationException
     */
    public function changeDefaultRole(TeamDefaultRoleFormRequest $request, Team $team): Response
    {
        $this->authorize('change-default-role', $team);

        $team->update(['default_role' => $request->get('default_role')]);

        return response()->noContent();
    }

    /**
     * @return array|Application|ResponseFactory|Response
     *
     * @throws Exception
     */
    public function changeRole(Request $request, Team $team)
    {
        $this->authorize('change-role', $team);

        /** @var User $user */
        $user = Auth::user();
        /** @var User $targetUser */
        $targetUser = User::where('name', $request->get('username'))->firstOrFail();
        $role       = $request->get('role');

        // Only if the current user may do such a thing
        if ($team->canChangeRole($user, $targetUser, $role)) {
            $team->changeRole($targetUser, $role);
            $result = response()->noContent();
        } else {
            $result = response('Forbidden', Http::FORBIDDEN);
        }

        return $result;
    }

    /**
     * @return array|Application|ResponseFactory|Response
     *
     * @throws Exception
     */
    public function addRoute(Request $request, Team $team, DungeonRoute $dungeonroute)
    {
        $this->authorize('moderate-route', $team);

        /** @var User $user */
        $user = Auth::user();

        if ($team->canAddRemoveRoute($user)) {
            $team->addRoute($dungeonroute);
            $result = response()->noContent();
        } else {
            $result = response('Forbidden', Http::FORBIDDEN);
        }

        return $result;
    }

    /**
     * @return array|Application|ResponseFactory|Response
     *
     * @throws Exception
     */
    public function removeRoute(Request $request, Team $team, DungeonRoute $dungeonroute)
    {
        $this->authorize('moderate-route', $team);

        /** @var User $user */
        $user = Auth::user();

        if ($team->canAddRemoveRoute($user)) {
            $team->removeRoute($dungeonroute);
            $result = response()->noContent();
        } else {
            $result = response('Forbidden', Http::FORBIDDEN);
        }

        return $result;
    }

    /**
     * @return array|Application|ResponseFactory|Response
     *
     * @throws Exception
     */
    public function removeMember(Request $request, Team $team, User $user)
    {
        $this->authorize('remove-member', [$team, $user]);

        // Only when successful
        if ($team->removeMember($user)) {
            $result = response()->noContent();

            // Disband if no team members are left
            if ($team->members->isEmpty()) {
                $team->delete();
            } else if ($team->isUserAdmin($user)) {
                // Promote someone else to be the new admin
                $newAdmin = $team->getNewAdminUponAdminAccountDeletion($user);
                if ($newAdmin !== null) {
                    $team->changeRole(
                        $newAdmin,
                        TeamUser::ROLE_ADMIN
                    );
                }
            }
        } else {
            $result = response('Unable to remove member from team', Http::INTERNAL_SERVER_ERROR);
        }

        return $result;
    }

    /**
     * Invalidate the current invite link and generate a new one.
     *
     * @return array
     *
     * @throws Exception
     */
    public function refreshInviteLink(Request $request, Team $team)
    {
        $this->authorize('refresh-invite-link', $team);

        $team->invite_code = Team::generateRandomPublicKey(12, 'invite_code');
        $team->save();

        return ['new_invite_link' => route('team.invite', ['invitecode' => $team->invite_code])];
    }

    /**
     * @return array|Application|ResponseFactory|Response
     *
     * @throws AuthorizationException
     */
    public function addAdFreeGiveaway(Request $request, Team $team, User $user): PatreonAdFreeGiveaway
    {
        $this->authorize('can-ad-free-giveaway', $team);

        /** @var User $currentUser */
        $currentUser = Auth::user();

        if (PatreonAdFreeGiveaway::getCountLeft($currentUser) <= 0) {
            abort(422, 'Unable to add more ad-free giveaways. Limit reached.');
        }

        if ($user->hasPatreonBenefit(PatreonBenefit::AD_FREE)) {
            abort(422, 'Unable to add ad-free giveaways, user is already ad-free through their own Patreon subscription.');
        }

        if ($user->hasAdFreeGiveaway()) {
            abort(422, 'Unable to add ad-free giveaways, user is already ad-free through an existing giveaway.');
        }

        return PatreonAdFreeGiveaway::create([
            'giver_user_id'    => $currentUser->id,
            'receiver_user_id' => $user->id,
        ]);
    }

    /**
     * @return array|Application|ResponseFactory|Response
     *
     * @throws AuthorizationException
     */
    public function removeAdFreeGiveaway(Request $request, Team $team, User $user): Response
    {
        $this->authorize('can-ad-free-giveaway', $team);

        /** @var User $currentUser */
        $currentUser = Auth::user();

        if ($user->patreonAdFreeGiveaway === null) {
            abort(422, 'Unable to remove ad-free giveaway - user does not have any at the moment.');
        }

        if ($user->patreonAdFreeGiveaway->giver_user_id !== $currentUser->id) {
            abort(422, 'Unable to remove ad-free giveaways that was not originally given by you.');
        }

        $user->patreonAdFreeGiveaway->delete();

        return response()->noContent();
    }
}
