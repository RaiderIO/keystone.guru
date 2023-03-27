<?php

namespace App\Http\Controllers;

use App\Http\Requests\Team\TeamDefaultRoleFormRequest;
use App\Models\DungeonRoute;
use App\Models\Team;
use App\Models\TeamUser;
use App\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;

class APITeamController extends Controller
{
    function list(Request $request)
    {
        return Auth::user()->teams()->get();
    }

    /**
     * @param TeamDefaultRoleFormRequest $request
     * @param Team $team
     * @return Response
     * @throws AuthorizationException
     */
    public function changeDefaultRole(TeamDefaultRoleFormRequest $request, Team $team)
    {
        $this->authorize('change-default-role', $team);

        $team->update(['default_role' => $request->get('default_role')]);

        return response()->noContent();
    }

    /**
     * @param Request $request
     * @param Team $team
     * @return array|Application|ResponseFactory|Response
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
     * @param Request $request
     * @param Team $team
     * @param DungeonRoute $dungeonroute
     * @return array|Application|ResponseFactory|Response
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
     * @param Request $request
     * @param Team $team
     * @param DungeonRoute $dungeonroute
     * @return array|Application|ResponseFactory|Response
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
     * @param Request $request
     * @param Team $team
     * @param User $user
     * @return array|Application|ResponseFactory|Response
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
     * @param Request $request
     * @param Team $team
     * @return array
     * @throws Exception
     */
    public function refreshInviteLink(Request $request, Team $team)
    {
        $this->authorize('refresh-invite-link', $team);

        $team->invite_code = Team::generateRandomPublicKey(12, 'invite_code');
        $team->save();

        return ['new_invite_link' => route('team.invite', ['invitecode' => $team->invite_code])];
    }
}
