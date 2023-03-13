<?php


namespace App\Http\Controllers;

use App\Events\Model\ModelChangedEvent;
use App\Http\Controllers\Traits\ChangesMapping;
use App\Models\Mapping\MappingModelInterface;
use Closure;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Base class for all models that are mapping versionable
 * @package App\Http\Controllers
 * @author Wouter
 * @since 06/11/2022
 */
abstract class APIMappingModelBaseController extends Controller
{
    use ChangesMapping;

    protected function shouldCallMappingChanged(?MappingModelInterface $beforeModel, ?MappingModelInterface $afterModel): bool
    {
        return true;
    }

    /**
     * @param array $validated
     * @param string $modelClass
     * @param MappingModelInterface|null $model
     * @param Closure|null $onSaveSuccess
     * @return Model
     * @throws Exception|Throwable
     */
    protected function storeModel(array $validated, string $modelClass, MappingModelInterface $model = null, Closure $onSaveSuccess = null): Model
    {
        /** @var Model $modelClass */
        return DB::transaction(function () use ($validated, $modelClass, $model, $onSaveSuccess) {
            /** @var Model|null $beforeModel */
            $beforeModel = $model === null ? null : clone $model;

            if ($model === null) {
                $model   = $modelClass::create($validated);
                $success = $model instanceof $modelClass;
            } else {
                $success = $model->update($validated);
            }

            if ($success) {
                if ($onSaveSuccess != null) {
                    $onSaveSuccess($model);
                }

                // Trigger mapping changed event so the mapping gets saved across all environments
                if ($this->shouldCallMappingChanged($beforeModel, $model)) {
                    $this->mappingChanged($beforeModel, $model);
                }

                if (Auth::check()) {
                    broadcast(new ModelChangedEvent($model->floor->dungeon, Auth::getUser(), $model));
                }

                return $model;
            } else {
                throw new Exception('Unable to save model!');
            }
        });
    }
}
