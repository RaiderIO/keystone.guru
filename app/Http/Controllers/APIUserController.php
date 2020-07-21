<?php

namespace App\Http\Controllers;


use App\Models\UserReport;
use App\User;
use Illuminate\Http\Request;

class APIUserController
{
    /**
     * @param $request
     * @return array|mixed
     * @throws \Exception
     */
    public function list(Request $request)
    {
        /** @var User[] $users */
        $users = User::with(['patreondata', 'roles', 'dungeonroutes'])->get();

        foreach($users as $user){
            $user->makeVisible(['id', 'name', 'created_at', 'patreondata', 'roles_string', 'routes']);
            $user->roles_string = $user->roles->pluck(['display_name'])->join(', ');
            $user->routes = $user->dungeonroutes->count();
            $user->unsetRelations(['roles', 'dungeonroutes']);
        }
        return $users;
    }
}