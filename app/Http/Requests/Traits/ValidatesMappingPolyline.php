<?php

namespace App\Http\Requests\Traits;

use App\Models\Polyline;
use App\Rules\JsonStringCountRule;

trait ValidatesMappingPolyline
{
    /**
     * The rules for the nested `polyline` object of a mapping model that owns a polyline.
     *
     * @return array<string, mixed>
     */
    protected function mappingPolylineRules(): array
    {
        return [
            'polyline.color'          => 'string',
            'polyline.color_animated' => 'nullable|string',
            'polyline.weight'         => 'int',
            'polyline.vertices_json'  => [
                'required',
                'json',
                sprintf('max:%d', Polyline::VERTICES_JSON_MAX_LENGTH),
                new JsonStringCountRule(2),
            ],
        ];
    }
}
