<?php

namespace App\Http\Requests\Metric;

use App\Models\DungeonRoute\DungeonRoute;
use App\Models\Metrics\Metric;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class APIMetricFormRequest extends FormRequest
{
    /**
     * The models a metric may be recorded against - the only model the front-end ever reports here.
     *
     * @var array<int, class-string>
     */
    public const array ALLOWED_MODEL_CLASSES = [
        DungeonRoute::class,
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        return [
            'id'          => 'int',
            'model_id'    => 'nullable|int',
            'model_class' => ['nullable', 'string', Rule::in(self::ALLOWED_MODEL_CLASSES)],
            'category'    => Rule::in(Metric::ALL_CATEGORIES),
            'tag'         => Rule::in(Metric::ALL_TAGS),
            'value'       => 'int',
        ];
    }
}
