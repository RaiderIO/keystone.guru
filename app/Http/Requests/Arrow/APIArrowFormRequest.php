<?php

namespace App\Http\Requests\Arrow;

use App\Http\Requests\Traits\CastInputData;
use App\Models\Arrow;
use App\Models\Floor\Floor;
use App\Models\Polyline;
use App\Rules\JsonStringCountRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class APIArrowFormRequest extends FormRequest
{
    use CastInputData;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->castInputData($this, Arrow::class);
        $this->castInputData($this, Polyline::class, 'polyline');
    }

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
