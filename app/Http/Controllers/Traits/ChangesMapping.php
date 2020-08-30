<?php


namespace App\Http\Controllers\Traits;

use App\Models\Mapping\MappingChangeLog;
use Illuminate\Database\Eloquent\Model;

trait ChangesMapping
{
    function mappingChanged(Model $beforeModel, ?Model $afterModel)
    {
        (new MappingChangeLog([
            'model_id'     => $beforeModel->id ?? $afterModel->id,
            'model_class'  => get_class($beforeModel),
            'before_model' => json_encode($beforeModel->toArray()),
            'after_model'  => $afterModel !== null ? json_encode($afterModel->toArray()) : null
        ]))->save();
    }
}