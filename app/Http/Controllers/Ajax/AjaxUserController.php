<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserFormRequest;
use App\Logic\Datatables\UsersDatatablesHandler;
use App\Models\User;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Teapot\StatusCode;

class AjaxUserController extends Controller
{
    /**
     * @return array|mixed
     *
     * @throws Exception
     */
    public function get(Request $request)
    {
        $users = User::with(['patreonUserLink', 'roles', 'dungeonroutes'])->selectRaw('users.*');

        $datatablesResult = (new UsersDatatablesHandler($request))->setBuilder($users)->applyRequestToBuilder()->getResult();

        foreach ($datatablesResult['data'] as $user) {
            /** @var $user User */
            $user->makeVisible(['id', 'name', 'email', 'created_at', 'patreonUserLink', 'roles_string', 'routes']);
            $user->roles_string = $user->roles->pluck(['display_name'])->join(', ');
            $user->routes       = $user->dungeonRoutes()->count();
            $user->unsetRelation('roles')->unsetRelation('dungeonroutes');
        }

        return $datatablesResult;
    }

    public function store(UserFormRequest $request, string $publicKey): User
    {
        /** @var User|null $user */
        $user = User::where('public_key', $publicKey)->first();

        if ($user === null || $user->public_key !== Auth::user()->public_key) {
            abort(StatusCode::BAD_REQUEST);
        }

        $user->update($request->validated());

        return $user->makeVisible('map_facade_style');
    }
}
