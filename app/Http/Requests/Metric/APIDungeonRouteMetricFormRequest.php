<?php

namespace App\Http\Requests\Metric;

use Override;

class APIDungeonRouteMetricFormRequest extends APIMetricFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    #[Override]
    /**

     * @return array<string, array<int, string|Rule>|string|Rule>
     */

    public function rules(): array
    {
        $rules = parent::rules();

        unset($rules['model_id']);
        unset($rules['model_class']);

        return $rules;
    }
}
