<?php

namespace App\Http\Controllers\Ajax;

use App\Events\Models\ModelChangedEvent;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Models\Mapping\MappingModelInterface;
use App\Models\Mapping\MappingVersion;
use App\Models\User;
use App\Service\Coordinates\CoordinatesServiceInterface;
use Closure;
use DB;
use Exception;
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
    use ChangesMapping;

    protected function shouldCallMappingChanged(
        ?MappingModelInterface $beforeModel,
        ?MappingModelInterface $afterModel,
    ): bool {
        return true;
    }

    /**
     * @throws Throwable
     */
    protected function storeModel(
        CoordinatesServiceInterface $coordinatesService,
        ?MappingVersion             $mappingVersion,
        array                       $validated,
        string                      $modelClass,
        ?MappingModelInterface      $model = null,
        ?Closure                    $onSaveSuccess = null,
        ?Model                      $echoContext = null,
    ): Model {
        $validated['mapping_version_id'] = $mappingVersion?->id;

        if (!is_a($modelClass, Model::class, true)) {
            throw new Exception(sprintf('Class %s is not a model!', $modelClass));
        }

        /** @var Model $modelClass */
        return DB::transaction(function () use (
            $coordinatesService,
            $validated,
            $modelClass,
            $model,
            $onSaveSuccess,
            $echoContext
        ) {
            /** @var Model|null $beforeModel */
            $beforeModel = $model === null ? null : clone $model;

            if ($model === null) {
                $model   = $modelClass::create($validated);
                $success = $model instanceof $modelClass;
            } else {
                $success = $model->update($validated);
            }

            if ($success) {
                $model->load([
                    'mappingVersion',
                    'floor',
                    'floor.dungeon',
                ]);

                if ($onSaveSuccess != null) {
                    $onSaveSuccess($model);
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                if ($this->shouldCallMappingChanged($beforeModel, $model)) {
                    $this->mappingChanged($beforeModel, $model);
                }

                if (Auth::check()) {
                    $echoContext = $echoContext ?? $model->floor->dungeon;
                    broadcast($this->getModelChangedEvent($coordinatesService, $echoContext, Auth::user(), $model));
                }

                return $model;
            } else {
                throw new Exception('Unable to save model!');
            }
        });
    }

    abstract protected function getModelChangedEvent(
        CoordinatesServiceInterface $coordinatesService,
        Model                       $context,
        User                        $user,
        Model                       $model,
    ): ModelChangedEvent;
}
