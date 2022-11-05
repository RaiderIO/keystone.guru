<?php

namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Controllers\Traits\ChecksForDuplicates;
use App\Http\Requests\EnemyPack\EnemyPackFormRequest;
use App\Models\EnemyPack;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class APIEnemyPackController extends Controller
{
    use ChangesMapping;
    use ChecksForDuplicates;

    /**
     * @param EnemyPackFormRequest $request
     * @param EnemyPack|null $enemyPack
     * @return EnemyPack
     * @throws Exception
     */
    function store(EnemyPackFormRequest $request, EnemyPack $enemyPack = null): EnemyPack
    {
        $validated = $request->validated();

        /** @var EnemyPack|null $beforeEnemyPack */
        $beforeEnemyPack = $enemyPack === null ? null : clone $enemyPack;

        $validated['vertices_json'] = json_encode($request->get('vertices'));
        unset($validated['vertices']);

        if ($enemyPack === null) {
            $enemyPack = EnemyPack::create($validated);
            $success   = $enemyPack instanceof EnemyPack;
        } else {
            $success = $enemyPack->update($validated);
        }

        if ($success) {
            if (Auth::check()) {
                broadcast(new ModelChangedEvent($enemyPack->floor->dungeon, Auth::getUser(), $enemyPack));
            }

            // Trigger mapping changed event so the mapping gets saved across all environments
            $this->mappingChanged($beforeEnemyPack, $enemyPack);
        } else {
            throw new Exception('Unable to save pack!');
        }

        return $enemyPack;
    }

    /**
     * @param Request $request
     * @param EnemyPack $enemypack
     * @return Response
     * @throws Exception
     */
    function delete(Request $request, EnemyPack $enemypack): Response
    {
        if ($enemypack->delete()) {
            if (Auth::check()) {
                broadcast(new ModelDeletedEvent($enemypack->floor->dungeon, Auth::getUser(), $enemypack));
            }

            // Trigger mapping changed event so the mapping gets saved across all environments
            $this->mappingChanged($enemypack, null);
            $result = response()->noContent();
        } else {
            throw new Exception('Unable to delete pack!');
        }

        return $result;
    }
}
