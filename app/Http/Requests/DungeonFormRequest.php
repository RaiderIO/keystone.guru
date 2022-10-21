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
            'active'                          => 'boolean',
            'speedrun_enabled'                => 'boolean',
            'zone_id'                         => 'int',
            'map_id'                          => 'int',
            'mdt_id'                          => 'int',
            'name'                            => ['required', Rule::unique('dungeons', 'name')->ignore($this->get('name'), 'name')],
            'key'                             => ['required', Rule::unique('dungeons', 'key')->ignore($this->get('key'), 'key')],
            'slug'                            => ['required', Rule::unique('dungeons', 'slug')->ignore($this->get('slug'), 'slug')],
            'enemy_forces_required'           => 'int',
            'enemy_forces_required_teeming'   => 'int',
            'enemy_forces_shrouded'           => 'int',
            'enemy_forces_shrouded_zul_gamux' => 'int',
            'timer_max_seconds'               => 'int',
        ];
    }
}
