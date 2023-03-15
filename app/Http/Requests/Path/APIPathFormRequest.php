<?php

namespace App\Http\Requests\Path;

use App\Models\Floor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class APIPathFormRequest extends FormRequest
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
            'id'                         => 'nullable|int',
            'floor_id'                   => ['nullable', Rule::exists(Floor::class, 'id')],
            'polyline'                   => 'array',
            'polyline.color'             => [
                'nullable',
                'string',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'polyline.color_animated'    => [
                'nullable',
                'string',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'polyline.weight'            => [
                'int',
            ],
            'polyline.vertices_json'     => [
                'string',
            ],
            'linked_awakened_obelisk_id' => 'nullable|int',
        ];
    }
}
