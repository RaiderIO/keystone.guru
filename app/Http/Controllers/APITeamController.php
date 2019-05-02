<?php

namespace App\Http\Controllers;

use App\Models\DungeonRoute;
use App\Models\Team;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class APITeamController extends Controller
{
    function list(Request $request)
    {
        return Auth::user()->teams()->get();
    }

    /**
     * @param Request $request
     * @param Team $team
     * @return array
     * @throws \Exception
     */
    public function changeRole(Request $request, Team $team)
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var User $targetUser */
        $targetUser = User::where('name', $request->get('username'))->firstOrFail();
        $role = $request->get('role');

        $result = ['result' => 'error'];

        // Only if the current user may do such a thing
        if ($team->canChangeRole($user, $targetUser, $role)) {
            $team->changeRole($targetUser, $role);
            $result = ['result' => 'success'];
        } else {
            abort(403, 'Unauthorized');
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param Team $team
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws \Exception
     */
    public function addRoute(Request $request, Team $team, DungeonRoute $dungeonroute)
    {
        /** @var User $user */
        $user = Auth::user();

        $result = ['result' => 'error'];
        if ($team->canAddRemoveRoute($user)) {
            $team->addRoute($dungeonroute);
            $result = ['result' => 'success'];
        } else {
            abort(403, 'Unauthorized');
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param Team $team
     * @param DungeonRoute $dungeonroute
     * @return array
     * @throws \Exception
     */
    public function removeRoute(Request $request, Team $team, DungeonRoute $dungeonroute)
    {
        /** @var User $user */
        $user = Auth::user();

        $result = ['result' => 'error'];
        if ($team->canAddRemoveRoute($user)) {
            $team->removeRoute($dungeonroute);
            $result = ['result' => 'success'];
        } else {
            abort(403, 'Unauthorized');
        }

        return $result;
    }

    /**
     * @param Request $request
     * @param Team $team
     * @param User $user
     * @return array
     * @throws \Exception
     */
    public function removeMember(Request $request, Team $team, User $user)
    {
        dd($user);
        $result = ['result' => 'error'];
        if ($team->canRemoveMember(Auth::user(), $user)) {
            $team->removeMember($user);
            $result = ['result' => 'success'];
        } else {
            abort(403, 'Unauthorized');
        }

        return $result;
    }
}
