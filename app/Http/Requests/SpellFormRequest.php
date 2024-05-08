<?php

namespace App\Http\Requests;

use App\Models\Laratrust\Role;
use App\Models\Spell;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SpellFormRequest extends FormRequest
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
            'id'             => 'required',
            'name'           => 'required|string',
            'icon_name'      => 'required|string',
            'category'       => Rule::in(Spell::ALL_CATEGORIES),
            'dispel_type'    => Rule::in(Spell::ALL_DISPEL_TYPES),
            'cooldown_group' => Rule::in(Spell::ALL_COOLDOWN_GROUPS),
            'schools'        => 'array',
            'schools.*'      => Rule::in(Spell::ALL_SCHOOLS),
            'aura'           => 'boolean',
            'selectable'     => 'boolean',
        ];
    }
}
