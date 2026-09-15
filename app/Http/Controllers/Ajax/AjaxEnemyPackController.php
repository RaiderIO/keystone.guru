<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\EnemyPack\EnemyPackChangedEvent;
use App\Events\Models\EnemyPack\EnemyPackDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Requests\EnemyPack\EnemyPackFormRequest;
use App\Models\EnemyPack;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use DB;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AjaxEnemyPackController extends AjaxMappingModelBaseController
{
    /**
     * @throws Throwable
     */
    public function store(
        EnemyPackFormRequest        $request,
        CoordinatesServiceInterface $coordinatesService,
        MappingVersion              $mappingVersion,
        ?EnemyPack                  $enemyPack = null,
    ): EnemyPack {
        $validated = $request->validated();

        /** @var EnemyPack */
        return $this->storeModel($coordinatesService, $mappingVersion, $validated, EnemyPack::class, $enemyPack);
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function delete(Request $request, MappingVersion $mappingVersion, EnemyPack $enemyPack): Response
    {
        // route:cache serializes this method; a body whose only $this usage sits inside a
        // nested closure is reconstructed unbound. Delegating keeps a top-level $this read
        // here, and the closures below compile normally inside a regular method (#4329).
        return $this->deleteEnemyPack($request, $mappingVersion, $enemyPack);
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    private function deleteEnemyPack(Request $request, MappingVersion $mappingVersion, EnemyPack $enemyPack): Response
    {
        return DB::transaction(function () use ($enemyPack) {
            if ($enemyPack->delete()) {
                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($enemyPack, null);

                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::getUser();

                    try {
                        broadcast(new EnemyPackDeletedEvent($enemyPack->floor->dungeon, $user, $enemyPack));
                    } catch (BroadcastException) {
                        // Ignore broadcast failures
                    }
                }

                $result = response()->noContent();
            } else {
                throw new Exception('Unable to delete pack!');
            }

            return $result;
        });
    }

    protected function getModelChangedEvent(
        CoordinatesServiceInterface $coordinatesService,
        Model                       $context,
        User                        $user,
        Model                       $model,
    ): ModelChangedEvent {
        return new EnemyPackChangedEvent($context, $user, $model);
    }
}
