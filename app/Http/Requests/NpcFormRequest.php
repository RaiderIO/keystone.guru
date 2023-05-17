<?php

namespace App\Http\Requests;

use App\Models\Dungeon;
use App\Models\Npc;
use App\Models\NpcClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NpcFormRequest extends FormRequest
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
        $rules = [
            'id'                        => ['required'],
            'name'                      => 'required',
            'dungeon_id'                => [Rule::in([-1] + Dungeon::all()->pluck('id')->toArray())],
            'npc_class_id'              => Rule::in(array_values(NpcClass::ALL)),
            'classification_id'         => 'required',
            'aggressiveness'            => Rule::in(Npc::ALL_AGGRESSIVENESS),
            'base_health'               => [
                'required',
                'regex:/^[\d\s,]*$/',
            ],
            'health_percentage'         => 'int',
            'enemy_forces'              => 'int',
            'enemy_forces_teeming'      => 'int',
            'dangerous'                 => 'bool',
            'truesight'                 => 'bool',
            'bursting'                  => 'bool',
            'bolstering'                => 'bool',
            'sanguine'                  => 'bool',
            'bolstering_whitelist_npcs' => 'array',
            'spells'                    => 'array',
        ];

        return $rules;
    }
}
