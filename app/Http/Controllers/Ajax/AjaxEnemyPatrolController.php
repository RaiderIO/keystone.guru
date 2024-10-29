<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Model\ModelDeletedEvent;
use App\Http\Controllers\Traits\SavesPolylines;
use App\Http\Requests\EnemyPatrol\EnemyPatrolFormRequest;
use App\Models\EnemyPatrol;
use App\Models\Mapping\MappingVersion;
use App\Models\Polyline;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxEnemyPatrolController extends AjaxMappingModelBaseController
{
    use SavesPolylines;

    /**
     * @return EnemyPatrol|Model
     *
     * @throws Throwable
     */
    public function store(
        CoordinatesServiceInterface $coordinatesService,
        EnemyPatrolFormRequest      $request,
        MappingVersion              $mappingVersion,
        ?EnemyPatrol                $enemyPatrol = null
    ): EnemyPatrol {
        $validated = $request->validated();

        return $this->storeModel(
            $mappingVersion,
            $validated,
            EnemyPatrol::class,
            $enemyPatrol,
            function (EnemyPatrol $enemyPatrol) use ($coordinatesService, $validated) {
                // A bit of a hack but disable the facade status of the mapping version - when editing an enemy patrol
                // we use the admin panel, which NEVER uses the facade view since we're editing.
                $enemyPatrol->mappingVersion->facade_enabled = false;

                // Create a new polyline and save it
                $this->savePolylineToModel(
                    $coordinatesService,
                    $enemyPatrol->mappingVersion,
                    Polyline::findOrNew($enemyPatrol->polyline_id),
                    $enemyPatrol,
                    $validated['polyline']
                );

                return true;
            }
        );
    }

    /**
     * @return Response
     */
    public function delete(Request $request, MappingVersion $mappingVersion, EnemyPatrol $enemyPatrol): Response
    {
        try {
            if ($enemyPatrol->delete()) {
                if (Auth::check()) {
                    /** @var User $user */
                    $user = Auth::getUser();
                    broadcast(new ModelDeletedEvent($enemyPatrol->floor->dungeon, $user, $enemyPatrol));
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                $this->mappingChanged($enemyPatrol, null);
            }

            $result = response()->noContent();
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }
}
