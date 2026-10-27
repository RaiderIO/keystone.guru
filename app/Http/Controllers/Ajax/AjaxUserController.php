<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserFormRequest;
use App\Http\Requests\User\UserToggleRoleFormRequest;
use App\Logic\Datatables\ColumnHandler\Users\EmailColumnHandler;
use App\Logic\Datatables\ColumnHandler\Users\IdColumnHandler;
use App\Logic\Datatables\ColumnHandler\Users\NameColumnHandler;
use App\Logic\Datatables\UsersDatatablesHandler;
use App\Models\Laratrust\Role;
use App\Models\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AjaxUserController extends Controller
{
    /**
     * @return array|mixed
     *
     * @throws Exception
     */
    public function get(Request $request)
    {
        $users = User::with([
            'patreonUserLink',
            // The link's manually_granted attribute reads this relation, so without it every row on
            // the page would fire its own query for it
            'patreonUserLink.activeManualGrant',
            'roles',
            'dungeonRoutes',
            'ipAddresses',
        ])
            ->selectRaw('users.*');

        $datatablesHandler = (new UsersDatatablesHandler($request));

        $datatablesResult = $datatablesHandler
            ->addColumnHandler([
                new IdColumnHandler($datatablesHandler),
                new EmailColumnHandler($datatablesHandler),
                new NameColumnHandler($datatablesHandler),
            ])
            ->setBuilder($users)
            ->applyRequestToBuilder()
            ->getResult();

        foreach ($datatablesResult['data'] as $user) {
            /** @var $user User */
            $user->makeVisible([
                'id',
                'name',
                'email',
                'created_at',
                'patreonUserLink',
                'roles_string',
                'role_names',
                'routes',
                'ip_addresses_string',
            ]);
            $user->roles_string        = $user->roles->pluck(['display_name'])->join(', ');
            $user->role_names          = $user->roles->pluck('name')->values();
            $user->routes              = $user->dungeonRoutes->count();
            $user->ip_addresses_string = $user->ipAddresses->pluck('ip_address')->join(',');
            $user->unsetRelation('roles')->unsetRelation('dungeonRoutes');
        }

        return $datatablesResult;
    }

    /**
     * @throws AuthorizationException
     */
    public function store(UserFormRequest $request, string $publicKey): User
    {
        /** @var User $user */
        $user = User::where('public_key', $publicKey)->firstOrFail();

        Gate::authorize('update', $user);

        $user->update($request->validated());

        return $user->makeVisible(['map_facade_style', 'kill_zone_path_weight']);
    }

    /**
     * Grants the role to the user, or revokes it when they already have it. The plain user role is
     * the exception: it resets the user to having only that role, since every account keeps it.
     *
     * @throws AuthorizationException
     */
    public function toggleRole(UserToggleRoleFormRequest $request, User $user): JsonResponse
    {
        $role = $request->role();

        Gate::authorize('makeRole', [$user, $role->name]);

        if ($role->name === Role::ROLE_USER) {
            $user->syncRoles([$role]);
            $hasRole = true;
        } elseif ($user->hasRole($role->name)) {
            $user->removeRole($role);
            $hasRole = false;
        } else {
            $user->addRole($role);
            $hasRole = true;
        }

        return response()->json([
            'role'     => $role->name,
            'has_role' => $hasRole,
        ]);
    }
}
