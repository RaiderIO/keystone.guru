<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\Brushline\BrushlineChangedEvent;
use App\Events\Models\Brushline\BrushlineDeletedEvent;
use App\Events\Models\ModelChangedEvent;
use App\Http\Controllers\Traits\EnforcesDungeonRouteLimits;
use App\Http\Controllers\Traits\ValidatesFloorId;
use App\Http\Requests\Brushline\APIBrushlineFormRequest;
use App\Models\Brushline;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\DungeonRoute\DungeonRouteLimitType;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Override;
use Teapot\StatusCode\Http;
use Throwable;

class AjaxBrushlineController extends AjaxMappingModelBaseController
{
    use EnforcesDungeonRouteLimits;
    use ValidatesFloorId;

    /**
     * @return Brushline|Response
     *
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function store(
        APIBrushlineFormRequest     $request,
        CoordinatesServiceInterface $coordinatesService,
        DungeonRoute                $dungeonRoute,
        ?Brushline                  $brushline = null,
    ) {
        $dungeonRoute = $brushline?->dungeonRoute ?? $dungeonRoute; // @phpstan-ignore nullsafe.neverNull

        Gate::authorize('edit', $dungeonRoute);
        $this->abortIfDungeonRouteLimitReached($dungeonRoute, DungeonRouteLimitType::Brushlines);

        $validated = $request->validated();

        $result = $this->validateFloorId($validated['floor_id'], $dungeonRoute->dungeon_id);
        if ($result !== null) {
            return $result;
        }

        try {
            $result = $this->storeModel(
                $coordinatesService,
                null,
                array_merge($validated, [
                    'dungeon_route_id' => $dungeonRoute->id,
                    'polyline_id'      => $brushline?->polyline_id ?? -1, // @phpstan-ignore nullsafe.neverNull
                ]),
                Brushline::class,
                $brushline,
                null,
                $dungeonRoute,
            );
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    /**
     * Returns the coordinate data that was dropped from the brushline-changed broadcast (#3909) -
     * collaborating clients call this after receiving that event instead.
     *
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function show(CoordinatesServiceInterface $coordinatesService, DungeonRoute $dungeonRoute, Brushline $brushline): array
    {
        $dungeonRoute = $brushline->dungeonRoute;

        Gate::authorize('view', $dungeonRoute);

        return [
            'model_data' => $brushline->polyline->getCoordinatesData(
                $coordinatesService,
                $dungeonRoute->mappingVersion,
                $brushline->floor,
            ),
        ];
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws AuthorizationException
     */
    public function delete(Request $request, DungeonRoute $dungeonRoute, Brushline $brushline)
    {
        // route:cache serializes this method; a body whose only $this usage sits inside a
        // nested closure is reconstructed unbound. Delegating keeps a top-level $this read
        // here, and the closures below compile normally inside a regular method (#4329).
        return $this->deleteBrushline($request, $dungeonRoute, $brushline);
    }

    /**
     * @return Response|ResponseFactory
     *
     * @throws AuthorizationException
     */
    private function deleteBrushline(Request $request, DungeonRoute $dungeonRoute, Brushline $brushline)
    {
        $dungeonRoute = $brushline->dungeonRoute;

        // Edit intentional; don't use delete rule because team members shouldn't be able to delete someone else's brush line
        Gate::authorize('edit', $dungeonRoute);

        try {
            $deleted = DB::transaction(function () use ($dungeonRoute, $brushline): bool {
                // Nothing has been written yet, so there is nothing to roll back
                if (!$brushline->delete()) {
                    return false;
                }

                $this->dungeonRouteChanged($dungeonRoute, $brushline, null);

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();

                return true;
            });

            if ($deleted) {
                // Broadcast only once the delete is committed, so no listener can read pre-commit state
                if (Auth::check()) {
                    /** @var \App\Models\User $user */
                    $user = Auth::getUser();

                    try {
                        broadcast(new BrushlineDeletedEvent($dungeonRoute, $user, $brushline));
                    } catch (BroadcastException) {
                        // We don't really care if the broadcast fails, so just catch the exception and move on
                    }
                }

                $result = response()->noContent();
            } else {
                $result = response(__('controller.brushline.error.unable_to_delete_brushline'), Http::INTERNAL_SERVER_ERROR);
            }
        } catch (Exception) {
            $result = response(__('controller.generic.error.not_found'), Http::NOT_FOUND);
        }

        return $result;
    }

    #[Override]
    protected function getModelChangedEvent(
        CoordinatesServiceInterface $coordinatesService,
        Model                       $context,
        User                        $user,
        Brushline|Model             $model,
    ): ModelChangedEvent {
        return new BrushlineChangedEvent($context, $user, $model);
    }
}
