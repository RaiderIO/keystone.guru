<?php

namespace App\Http\Requests\Spell;

use App\Models\GameVersion\GameVersion;
use App\Models\Laratrust\Role;
use App\Models\Spell\Spell;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AjaxSpellUpdateFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->hasRole(Role::ROLE_ADMIN) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name'            => 'string',
            'game_version_id' => ['nullable', Rule::exists(GameVersion::class, 'id')],
            'icon_name'       => 'string',
            'category'        => Rule::in(Spell::ALL_CATEGORIES),
            'dispel_type'     => Rule::in(Spell::ALL_DISPEL_TYPES),
            'cooldown_group'  => Rule::in(Spell::ALL_COOLDOWN_GROUPS),
            'schools'         => 'array',
            'schools.*'       => Rule::in(Spell::ALL_SCHOOLS),
            'aura'            => 'boolean',
            'selectable'      => 'boolean',
            'hidden_on_map'   => 'boolean',
        ];
    }
}
