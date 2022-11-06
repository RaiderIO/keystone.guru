<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Requests\EnemyPack\EnemyPackFormRequest;
use App\Models\EnemyPack;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Throwable;

class APIEnemyPackController extends Controller
{
    use ChangesMapping;
    use ChecksForDuplicates;

    /**
     * @param EnemyPackFormRequest $request
     * @param EnemyPack|null $enemyPack
     * @return EnemyPack
     * @throws Exception
     * @throws Throwable
     */
    public function store(EnemyPackFormRequest $request, EnemyPack $enemyPack = null): EnemyPack
    {
        $validated = $request->validated();

        /** @var EnemyPack|null $beforeEnemyPack */
        $beforeEnemyPack = $enemyPack === null ? null : clone $enemyPack;

        $validated['vertices_json'] = json_encode($request->get('vertices'));
        unset($validated['vertices']);

        return DB::transaction(function () use ($request, $validated, $beforeEnemyPack, $enemyPack) {
            if ($enemyPack === null) {
                $enemyPack = EnemyPack::create($validated);
                $success   = $enemyPack instanceof EnemyPack;
            } else {
                $success = $enemyPack->update($validated);
            }

            if ($success) {
                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($beforeEnemyPack, $enemyPack);

                if (Auth::check()) {
                    broadcast(new ModelChangedEvent($enemyPack->floor->dungeon, Auth::getUser(), $enemyPack));
                }
            } else {
                throw new Exception('Unable to save pack!');
            }

            return $enemyPack;
        });
    }

    /**
     * @param Request $request
     * @param EnemyPack $enemyPack
     * @return Response
     * @throws Exception
     */
    public function delete(Request $request, EnemyPack $enemyPack): Response
    {
        DB::transaction(function () use ($request, $enemyPack) {
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
