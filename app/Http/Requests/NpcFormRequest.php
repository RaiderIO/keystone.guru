<?php

namespace App\Http\Requests;

use App\Models\Dungeon;
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
        $npc = $this->route()->parameter('npc');

        $rules = [
            // Can only add one entry per game_id, but exclude if we're editing a row but don't change the game_id
            'id'                => ['required'],
            'name'              => 'required',
            'dungeon_id'        => [Rule::in([-1] + Dungeon::all()->pluck('id')->toArray())],
            'classification_id' => 'required',
            'aggressiveness'    => Rule::in(config('keystoneguru.aggressiveness')),
            'base_health'       => [
                'required',
                'regex:/^[\d\s,]*$/',
            ],
            'enemy_forces'      => 'int'
        ];

        // portrait is required when making a new npc, when editing it's optional
        if ($npc === null) {
            $rules['portrait'] = 'required|image|mimes:png|max:128';
        }

        return $rules;
    }
}
