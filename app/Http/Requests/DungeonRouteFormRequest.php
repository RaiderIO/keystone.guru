<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DungeonRouteFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // \Auth::user()->hasRole(["user", "admin"]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|string|min:3|max:255',
            'dungeon_id' => ['required', Rule::exists('dungeons', 'id')],
            'faction_id' => ['required', Rule::exists('factions', 'id')],

            'race' => 'nullable|array',
            'class' => 'nullable|array',

            'race.*' => 'nullable|numeric|min:1',
            'class.*' => 'nullable|numeric|min:1',
        ];
    }
}
