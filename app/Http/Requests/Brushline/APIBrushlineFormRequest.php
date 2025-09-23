<?php

namespace App\Http\Requests\Brushline;

use App\Http\Requests\Traits\CastInputData;
use App\Models\Brushline;
use App\Models\Floor\Floor;
use App\Models\Polyline;
use App\Rules\JsonStringCountRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class APIBrushlineFormRequest extends FormRequest
{
    use CastInputData;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->castInputData($this, Brushline::class);
        $this->castInputData($this, Polyline::class, 'polyline');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'floor_id' => [
                'required',
                Rule::exists(Floor::class, 'id'),
            ],
            'polyline'       => 'required|array',
            'polyline.color' => [
                'nullable',
                'string',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'polyline.color_animated' => [
                'nullable',
                'string',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'polyline.weight' => [
                'int',
            ],
            'polyline.vertices_json' => [
                'json',
                new JsonStringCountRule(2),
            ],
        ];
    }
}
