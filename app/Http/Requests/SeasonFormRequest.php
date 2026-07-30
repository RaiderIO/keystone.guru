<?php

namespace App\Http\Requests;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Models\Laratrust\Role;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SeasonFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        return [
            'expansion_id'            => ['required', Rule::exists(Expansion::class, 'id')],
            'seasonal_affix_id'       => 'nullable|integer|min:0',
            'index'                   => 'required|integer|min:1',
            'start'                   => 'required|date',
            'presets'                 => 'nullable|integer|min:0',
            'affix_group_count'       => 'required|integer|min:1',
            'start_affix_group_index' => 'required|integer|min:0|lt:affix_group_count',
            'key_level_min'           => 'required|integer|min:1',
            'key_level_max'           => 'required|integer|gte:key_level_min',
            'item_level_min'          => 'nullable|integer|min:0',
            'item_level_max'          => 'nullable|integer|gte:item_level_min',
            'dungeon_ids'             => 'nullable|array',
            'dungeon_ids.*'           => ['integer', Rule::exists(Dungeon::class, 'id')],
        ];
    }
}
