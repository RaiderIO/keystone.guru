<?php

namespace App\Http\Requests\Metric;

class APIDungeonRouteMetricFormRequest extends APIMetricFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = parent::rules();

        unset($rules['model_id']);
        unset($rules['model_class']);

        return $rules;
    }
}
