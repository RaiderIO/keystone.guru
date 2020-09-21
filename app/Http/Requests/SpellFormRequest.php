<?php

namespace App\Http\Requests;

use App\Models\Spell;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SpellFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $npc = $this->route()->parameter('npc');

        $rules = [
            'id'                   => 'required',
            'name'                 => 'required|string',
            'icon_name'            => 'required|string',
            'dispel_type'          => Rule::in(Spell::ALL_DISPEL_TYPES),
            'schools'              => 'array',
            'schools.*'            => Rule::in(Spell::ALL_SCHOOLS),
            'aura'                 => 'boolean'
        ];

        return $rules;
    }
}
