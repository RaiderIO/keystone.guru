<?php

namespace App\Http\Controllers\Ajax;


use App\Logic\Datatables\UsersDatatablesHandler;
use App\User;
use Illuminate\Http\Request;

class AjaxUserController
{
    /**
     * @param Request $request
     * @return array|mixed
     * @throws \Exception
     */
    public function list(Request $request)
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
}
