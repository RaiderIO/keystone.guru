<?php

namespace App\Http\Requests\DungeonRoute;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AjaxDungeonRouteDataFormRequest extends FormRequest
{
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
            'public_keys'   => 'array',
            'public_keys.*' => Rule::exists('dungeon_routes', 'public_key'),
        ];
    }
}
