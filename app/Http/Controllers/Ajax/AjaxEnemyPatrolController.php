<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\EnemyPatrol\EnemyPatrolChangedEvent;
use App\Events\Models\EnemyPatrol\EnemyPatrolDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Controllers\Traits\SavesPolylines;
use App\Http\Requests\EnemyPatrol\EnemyPatrolFormRequest;
use App\Models\EnemyPatrol;
use App\Models\Mapping\MappingVersion;
use App\Models\Polyline;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxEnemyPatrolController extends AjaxMappingModelBaseController
{
    use SavesPolylines;

    /**
     * @throws Throwable
     */
    public function store(
        CoordinatesServiceInterface $coordinatesService,
        EnemyPatrolFormRequest      $request,
        MappingVersion              $mappingVersion,
        ?EnemyPatrol                $enemyPatrol = null,
    ): EnemyPatrol {
        $validated = $request->validated();

        $beforeModel = $enemyPatrol !== null ? clone $enemyPatrol : null;

        /** @var EnemyPatrol */
        return $this->storeModel(
            $coordinatesService,
            $mappingVersion,
            array_merge($validated, [
                // Ensure we keep the mdt polyline ID if it was set
                'mdt_polyline_id' => $beforeModel?->mdt_polyline_id,
            ]),
            EnemyPatrol::class,
            $enemyPatrol,
            function (EnemyPatrol $enemyPatrol) use ($coordinatesService, $validated, $beforeModel) {
                // A bit of a hack but disable the facade status of the mapping version - when editing an enemy patrol
                // we use the admin panel, which NEVER uses the facade view since we're editing.
                $enemyPatrol->mappingVersion->facade_enabled = false;

                // Create a new polyline and save it
                $this->savePolylineToModel(
                    $coordinatesService,
                    null, // Disable saving changes - we don't need that
                    $enemyPatrol->mappingVersion,
                    Polyline::findOrNew($enemyPatrol->polyline_id),
                    $beforeModel,
                    $enemyPatrol,
                    $validated['polyline'],
                );

                return true;
            },
        );
    }

    /**
     * @return Response
     */
    public function delete(Request $request, MappingVersion $mappingVersion, EnemyPatrol $enemyPatrol): Response
    {
        // route:cache serializes this method; a body whose only $this usage sits inside a
        // nested closure is reconstructed unbound. Delegating keeps a top-level $this read
        // here, and the closures below compile normally inside a regular method (#4329).
        return $this->deleteEnemyPatrol($request, $mappingVersion, $enemyPatrol);
    }

    /**
     * @return Response
     */
    private function deleteEnemyPatrol(Request $request, MappingVersion $mappingVersion, EnemyPatrol $enemyPatrol): Response
    {
        try {
            $deleted = DB::transaction(function () use ($enemyPatrol): bool {
                // Nothing has been written yet, so there is nothing to roll back
                if (!$enemyPatrol->delete()) {
                    return false;
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($enemyPatrol, null);

                return true;
            });

            // Broadcast only once the delete is committed, so no listener can read pre-commit state
            if ($deleted && Auth::check()) {
                /** @var User $user */
                $user = Auth::getUser();

                try {
                    broadcast(new EnemyPatrolDeletedEvent($enemyPatrol->floor->dungeon, $user, $enemyPatrol));
                } catch (BroadcastException) {
                    // Ignore broadcast failures
                }
            }

            $result = response()->noContent();
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    protected function getModelChangedEvent(
        CoordinatesServiceInterface $coordinatesService,
        Model                       $context,
        User                        $user,
        Model                       $model,
    ): ModelChangedEvent {
        return new EnemyPatrolChangedEvent($context, $user, $model);
    }
}
