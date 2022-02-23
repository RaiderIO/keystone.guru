<?php


namespace App\Http\Controllers\Traits;

use App\Models\Mapping\MappingChangeLog;
use Exception;
use Illuminate\Database\Eloquent\Model;

trait ChangesMapping
{
    /**
     * @param Model|null $beforeModel
     * @param Model|null $afterModel
     * @return void
     * @throws Exception
     */
    function mappingChanged(?Model $beforeModel, ?Model $afterModel): void
    {
        if ($beforeModel === null && $afterModel === null) {
            throw new Exception('Must have at least a $beforeModel OR $afterModel');
        }

        (new MappingChangeLog([
            'model_id'     => optional($beforeModel)->id ?? $afterModel->id,
            'model_class'  => $beforeModel !== null ? get_class($beforeModel) : get_class($afterModel),
            'before_model' => optional($beforeModel)->exists ? json_encode($beforeModel->toArray()) : null,
            'after_model'  => $afterModel !== null ? json_encode($afterModel->toArray()) : null,
        ]))->save();
    }
}
