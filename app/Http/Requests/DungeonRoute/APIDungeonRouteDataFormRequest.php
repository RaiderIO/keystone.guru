<?php

namespace App\Http\Requests\DungeonRoute;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class APIDungeonRouteDataFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'public_keys' => 'array',
            'public_keys.*' => Rule::exists('dungeon_routes', 'public_key'),
        ];
    }
}
