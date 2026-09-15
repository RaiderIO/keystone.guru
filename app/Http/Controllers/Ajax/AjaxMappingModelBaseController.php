<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\ModelChangedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesDungeonRoute;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Http\Controllers\Traits\SavesPolylines;
use App\Logic\Structs\LatLng;
use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Floor\Floor;
use App\Models\Interfaces\HasLatLngInterface;
use App\Models\Interfaces\HasPolylineInterface;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\Polyline;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Closure;
use DB;
use Exception;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Base class for all models that are mapping versionable
 *
 * @author Wouter
 *
 * @since 06/11/2022
 */
abstract class AjaxMappingModelBaseController extends Controller
{
    use ChangesDungeonRoute;
    use ChangesMapping;
    use SavesPolylines;

    protected function shouldCallMappingChanged(
        ?MappingModelInterface $beforeModel,
        ?MappingModelInterface $afterModel,
    ): bool {
        return true;
    }

    /**
     * Whether coordinates posted on a facade floor should be converted back onto the floor they
     * actually belong to. False for the models that make up the facade itself - their coordinates
     * are facade coordinates by definition and converting them would corrupt the facade.
     */
    protected function shouldConvertFacadeCoordinates(): bool
    {
        return true;
    }

    /**
     * @param  array<string, mixed> $validated
     * @throws Throwable
     */
    protected function storeModel(
        CoordinatesServiceInterface $coordinatesService,
        ?MappingVersion             $mappingVersion,
        array                       $validated,
        string                      $modelClass,
        ?Model                      $model = null,
        ?Closure                    $onSaveSuccess = null,
        ?DungeonRoute               $dungeonRoute = null,
    ): Model {
        if (!is_a($modelClass, Model::class, true)) {
            throw new Exception(sprintf('Class %s is not a model!', $modelClass));
        }

        if (is_a($modelClass, MappingModelInterface::class, true)) {
            $validated['mapping_version_id'] = $mappingVersion?->id;
        }

        // The mapping version whose facade the incoming coordinates may be expressed in. Admin endpoints
        // pass the mapping version directly, route scoped endpoints inherit it from the route
        $facadeMappingVersion = $mappingVersion ?? $dungeonRoute?->mappingVersion;

        /** @var class-string<Model> $modelClass */
        return DB::transaction(function () use (
            $coordinatesService,
            $facadeMappingVersion,
            $dungeonRoute,
            $validated,
            $modelClass,
            $model,
            $onSaveSuccess
        ) {
            $beforeModel = $model === null ? null : clone $model;

            // The client posts the facade floor's id when it drew on the facade map - everything it
            // sent along with it is then expressed in facade coordinates and needs converting back
            $facadeFloor = $facadeMappingVersion === null || !$this->shouldConvertFacadeCoordinates() ?
                null : $this->getFacadeFloor($validated);
            $facadeLatLng = null;

            if ($facadeFloor !== null && is_a($modelClass, HasLatLngInterface::class, true)) {
                $facadeLatLng = new LatLng((float)$validated['lat'], (float)$validated['lng'], $facadeFloor);

                $latLng = $coordinatesService->convertFacadeMapLocationToMapLocation($facadeMappingVersion, $facadeLatLng);

                $validated['lat']      = $latLng->getLat();
                $validated['lng']      = $latLng->getLng();
                $validated['floor_id'] = $latLng->getFloor()?->id;
            }

            if ($model === null) {
                $model   = $modelClass::create($validated);
                $success = $model instanceof $modelClass;
            } else {
                $success = $model->update($validated);
            }

            if (!$success) {
                throw new Exception('Unable to save model!');
            }

            $model->load($this->getRelationsToLoad($model));

            $polyline = null;
            if ($model instanceof HasPolylineInterface && isset($validated['polyline'])) {
                $polyline = $this->savePolylineToModel(
                    $coordinatesService,
                    $dungeonRoute,
                    $facadeMappingVersion,
                    $facadeFloor,
                    Polyline::findOrNew($model->getAttribute('polyline_id')),
                    $model,
                    $validated['polyline'],
                );
            }

            if ($onSaveSuccess !== null) {
                $onSaveSuccess($model, $beforeModel);
            }

            if ($dungeonRoute !== null) {
                $this->dungeonRouteChanged($dungeonRoute, $beforeModel, $model);

                // Touch the route so that the thumbnail gets updated
                $dungeonRoute->touch();
            }

            // Trigger mapping changed event so the mapping gets saved across all environments
            if ($model instanceof MappingModelInterface) {
                $beforeMappingModel = $beforeModel instanceof MappingModelInterface ? $beforeModel : null;

                if ($this->shouldCallMappingChanged($beforeMappingModel, $model)) {
                    $this->mappingChanged($beforeMappingModel, $model);
                }
            }

            // The change logs above must record the real coordinates, so only now put the facade
            // coordinates back - the client drew those and cannot make sense of anything else
            if ($facadeFloor !== null) {
                $this->restoreFacadeCoordinates($model, $facadeFloor, $facadeLatLng, $polyline, $validated['polyline']['vertices_json'] ?? null);
            }

            if (Auth::check()) {
                /** @var Floor|null $floor */
                $floor       = $model->getAttribute('floor');
                $echoContext = $dungeonRoute ?? $floor?->dungeon;

                try {
                    broadcast($this->getModelChangedEvent($coordinatesService, $echoContext, Auth::user(), $model));
                } catch (BroadcastException) {
                    // Ignore broadcast failures
                }
            }

            return $model;
        });
    }

    /**
     * @param array<string, mixed> $validated
     */
    private function getFacadeFloor(array $validated): ?Floor
    {
        if (!isset($validated['floor_id'])) {
            return null;
        }

        /** @var Floor|null $floor */
        $floor = Floor::find($validated['floor_id']);

        return $floor?->facade ? $floor : null;
    }

    private function restoreFacadeCoordinates(
        Model     $model,
        Floor     $facadeFloor,
        ?LatLng   $facadeLatLng,
        ?Polyline $polyline,
        ?string   $facadeVerticesJson,
    ): void {
        if ($model instanceof HasLatLngInterface && $facadeLatLng !== null) {
            $model->setLatLng($facadeLatLng);
        }

        if ($polyline !== null && $facadeVerticesJson !== null) {
            $model->setAttribute('floor_id', $facadeFloor->id);
            $polyline->setAttribute('vertices_json', $facadeVerticesJson);
        }

        $model->setRelation('floor', $facadeFloor);
    }

    /** @return array<int, string> */
    private function getRelationsToLoad(Model $model): array
    {
        $relations = ['floor', 'floor.dungeon'];

        if ($model instanceof MappingModelInterface) {
            $relations[] = 'mappingVersion';
        }

        return $relations;
    }

    abstract protected function getModelChangedEvent(
        CoordinatesServiceInterface $coordinatesService,
        Model                       $context,
        User                        $user,
        Model                       $model,
    ): ModelChangedEvent;
}
