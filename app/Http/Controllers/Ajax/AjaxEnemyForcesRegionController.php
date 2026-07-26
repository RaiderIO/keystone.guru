<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\EnemyForcesRegion\EnemyForcesRegionChangedEvent;
use App\Events\Models\EnemyForcesRegion\EnemyForcesRegionDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Requests\EnemyForcesRegion\EnemyForcesRegionFormRequest;
use App\Models\EnemyForcesRegion;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use DB;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxEnemyForcesRegionController extends AjaxMappingModelBaseController
{
    /**
     * @return EnemyForcesRegion
     *
     * @throws Exception
     * @throws Throwable
     */
    public function store(
        EnemyForcesRegionFormRequest $request,
        CoordinatesServiceInterface  $coordinatesService,
        MappingVersion               $mappingVersion,
        ?EnemyForcesRegion           $enemyForcesRegion = null,
    ): EnemyForcesRegion {
        /** @var EnemyForcesRegion */
        return $this->storeModel(
            $coordinatesService,
            $mappingVersion,
            $request->validated(),
            EnemyForcesRegion::class,
            $enemyForcesRegion,
        );
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws Throwable
     */
    public function delete(Request $request, MappingVersion $mappingVersion, EnemyForcesRegion $enemyForcesRegion)
    {
        return DB::transaction(function () use ($enemyForcesRegion) {
            try {
                // The model's `deleted` hook releases its member enemies, so they don't keep pointing at
                // a region that no longer exists.
                if ($enemyForcesRegion->delete()) {
                    // Trigger mapping changed event so the mapping gets saved across all environments
                    $this->mappingChanged($enemyForcesRegion, null);

                    if (Auth::check()) {
                        /** @var User $user */
                        $user = Auth::user();
                        broadcast(new EnemyForcesRegionDeletedEvent($enemyForcesRegion->floor->dungeon, $user, $enemyForcesRegion));
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
        return new EnemyForcesRegionChangedEvent($context, $user, $model);
    }
}
