<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DungeonFormRequest extends FormRequest
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
        return [
            'name' => ['required', Rule::unique('dungeons')->ignore($this->route()->parameter('dungeon'))],
//            'expansion_id' => 'required',
            'enemy_forces_required' => 'int',
            'enemy_forces_required_teeming' => 'int',
        ];
    }
}
