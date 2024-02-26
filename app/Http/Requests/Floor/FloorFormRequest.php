<?php

namespace App\Http\Requests\Floor;

use Auth;
use Illuminate\Foundation\Http\FormRequest;

class FloorFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'active'                             => ['nullable', 'bool'],
            'default'                            => ['nullable', 'bool'],
            'facade'                             => ['nullable', 'bool'],
            'name'                               => ['required', 'string'],
            'index'                              => ['required', 'integer'],
            'mdt_sub_level'                      => ['nullable', 'integer'],
            'ui_map_id'                          => ['required', 'integer'],
            'min_enemy_size'                     => ['nullable', 'integer'],
            'max_enemy_size'                     => ['nullable', 'integer'],
            'enemy_engagement_max_range'         => ['nullable', 'integer'],
            'enemy_engagement_max_range_patrols' => ['nullable', 'integer'],
            'percentage_display_zoom'            => ['nullable', 'integer'],
        ];
    }
}
