<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UserFormRequest;
use App\Logic\Datatables\ColumnHandler\Users\EmailColumnHandler;
use App\Logic\Datatables\ColumnHandler\Users\IdColumnHandler;
use App\Logic\Datatables\ColumnHandler\Users\NameColumnHandler;
use App\Logic\Datatables\UsersDatatablesHandler;
use App\Models\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
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
                'routes',
                'ip_addresses_string',
            ]);
            $user->roles_string        = $user->roles->pluck(['display_name'])->join(', ');
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
}
