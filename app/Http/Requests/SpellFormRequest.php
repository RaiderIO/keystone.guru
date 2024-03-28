<?php

namespace App\Http\Requests;

use App\Models\Spell;
use App\Models\User;
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
        /** @var User $user */
        $user = Auth::user();

        return optional($user)->hasRole('admin') ?? false;
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
