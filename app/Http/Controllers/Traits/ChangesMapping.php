<?php


namespace App\Http\Controllers\Traits;

use App\Models\Mapping\MappingChangeLog;
use App\Models\Mapping\MappingModelInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;

trait ChangesMapping
{
    /**
     * @param Model|MappingModelInterface|null $beforeModel
     * @param Model|MappingModelInterface|null $afterModel
     * @return void
     * @throws Exception
     */
    public function mappingChanged(?MappingModelInterface $beforeModel, ?MappingModelInterface $afterModel): void
    {
        if ($beforeModel === null && $afterModel === null) {
            throw new Exception('Must have at least a $beforeModel OR $afterModel');
        }

        (new MappingChangeLog([
            'dungeon_id'   => optional($beforeModel)->getDungeonId() ?? $afterModel->getDungeonId(),
            'model_id'     => optional($beforeModel)->id ?? $afterModel->id,
            'model_class'  => ($beforeModel ?? $afterModel)::class,
            'before_model' => $beforeModel !== null ? json_encode($beforeModel->toArray()) : null,
            'after_model'  => $afterModel !== null ? json_encode($afterModel->toArray()) : null,
        ]))->save();
    }
}
