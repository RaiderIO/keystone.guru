<?php

namespace App\Http\Requests\Metric;

use App\Models\Metrics\Metric;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class APIMetricFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'id'          => 'int',
            'model_id'    => 'int|null',
            'model_class' => 'string|null',
            'category'    => Rule::in(Metric::ALL_CATEGORIES),
            'tag'         => Rule::in(Metric::ALL_TAGS),
            'value'       => 'int',
        ];
    }
}
