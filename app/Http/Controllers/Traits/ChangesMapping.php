<?php

namespace App\Http\Controllers\Traits;

use App\Models\Mapping\MappingChangeLog;
use App\Models\Mapping\MappingModelInterface;
use Exception;

trait ChangesMapping
{
    /**
     * @param MappingModelInterface|null $beforeModel
     * @param MappingModelInterface|null $afterModel
     *
     * @throws Exception
     */
    public function mappingChanged(?MappingModelInterface $beforeModel, ?MappingModelInterface $afterModel): void
    {
        if ($beforeModel === null && $afterModel === null) {
            throw new Exception('Must have at least a $beforeModel OR $afterModel');
        }

        MappingChangeLog::create([
            'dungeon_id'   => $beforeModel?->getDungeonId() ?? $afterModel->getDungeonId(),
            'model_id'     => $beforeModel?->id ?? $afterModel->id,
            'model_class'  => ($beforeModel ?? $afterModel)::class,
            'before_model' => $beforeModel !== null ? json_encode($beforeModel->toArray()) : null,
            'after_model'  => $afterModel !== null ? json_encode($afterModel->toArray()) : null,
        ]);
    }
}
