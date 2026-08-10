<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\EnemyForcesCheckpoint\EnemyForcesCheckpointChangedEvent;
use App\Events\Models\EnemyForcesCheckpoint\EnemyForcesCheckpointDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Requests\EnemyForcesCheckpoint\EnemyForcesCheckpointFormRequest;
use App\Models\EnemyForcesCheckpoint;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use DB;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxEnemyForcesCheckpointController extends AjaxMappingModelBaseController
{
    /**
     * @return EnemyForcesCheckpoint
     *
     * @throws Exception
     * @throws Throwable
     */
    public function store(
        EnemyForcesCheckpointFormRequest $request,
        CoordinatesServiceInterface      $coordinatesService,
        MappingVersion                   $mappingVersion,
        ?EnemyForcesCheckpoint           $enemyForcesCheckpoint = null,
    ): EnemyForcesCheckpoint {
        /** @var EnemyForcesCheckpoint */
        return $this->storeModel(
            $coordinatesService,
            $mappingVersion,
            $request->validated(),
            EnemyForcesCheckpoint::class,
            $enemyForcesCheckpoint,
        );
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws Throwable
     */
    public function delete(Request $request, MappingVersion $mappingVersion, EnemyForcesCheckpoint $enemyForcesCheckpoint)
    {
        return DB::transaction(function () use ($enemyForcesCheckpoint) {
            try {
                // The model's `deleted` hook releases its member enemies, so they don't keep pointing at
                // a checkpoint that no longer exists.
                if ($enemyForcesCheckpoint->delete()) {
                    // Trigger mapping changed event so the mapping gets saved across all environments
                    $this->mappingChanged($enemyForcesCheckpoint, null);

                    if (Auth::check()) {
                        /** @var User $user */
                        $user = Auth::user();

                        try {
                            broadcast(new EnemyForcesCheckpointDeletedEvent($enemyForcesCheckpoint->floor->dungeon, $user, $enemyForcesCheckpoint));
                        } catch (BroadcastException) {
                            // Ignore broadcast failures
                        }
                    }
                }

                $result = response()->noContent();
            } catch (Exception) {
                $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
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
        return new EnemyForcesCheckpointChangedEvent($context, $user, $model);
    }
}
