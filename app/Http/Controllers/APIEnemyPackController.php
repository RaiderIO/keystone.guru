<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Requests\EnemyPack\EnemyPackFormRequest;
use App\Models\EnemyPack;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Throwable;

class APIEnemyPackController extends APIMappingModelBaseController
{
    use ChangesMapping;
    use ChecksForDuplicates;

    /**
     * @param EnemyPackFormRequest $request
     * @param EnemyPack|null $enemyPack
     * @return EnemyPack|Model
     * @throws Exception
     * @throws Throwable
     */
    public function store(EnemyPackFormRequest $request, EnemyPack $enemyPack = null): EnemyPack
    {
        $validated = $request->validated();

        $validated['vertices_json'] = json_encode($request->get('vertices'));
        unset($validated['vertices']);

        return $this->storeModel($validated, EnemyPack::class, $enemyPack);
    }

    /**
     * @param Request $request
     * @param EnemyPack $enemyPack
     * @return Response
     * @throws Exception
     * @throws Throwable
     */
    public function delete(Request $request, EnemyPack $enemyPack): Response
    {
        return DB::transaction(function () use ($request, $enemyPack) {
            if ($enemyPack->delete()) {
                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($enemyPack, null);

                if (Auth::check()) {
                    broadcast(new ModelDeletedEvent($enemyPack->floor->dungeon, Auth::getUser(), $enemyPack));
                }

                $result = response()->noContent();
            } else {
                throw new Exception('Unable to delete pack!');
            }

            return $result;
        });
    }
}
