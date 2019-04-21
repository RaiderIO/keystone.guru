<?php

namespace App\Http\Controllers;

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
     * @return array
     * @throws \Exception
     */
    function changeRole(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Team $team */
        $team = Team::findOrFail($request->get('team_id'));
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
}
