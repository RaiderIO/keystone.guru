<?php

namespace App\Http\Requests;

use App\Models\Dungeon;
use App\Models\Laratrust\Role;
use App\Models\Npc\Npc;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NpcFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id'                        => 'required',
            'name'                      => 'required',
            'dungeon_id'                => [Rule::in(array_merge([-1], Dungeon::all('id')->pluck('id')->toArray()))],
            'npc_type_id'               => Rule::exists('npc_types', 'id'),
            'npc_class_id'              => Rule::exists('npc_classes', 'id'),
            'classification_id'         => [Rule::exists('npc_classifications', 'id'), 'required'],
            'aggressiveness'            => Rule::in(Npc::ALL_AGGRESSIVENESS),
            'base_health'               => [
                'required',
                'regex:/^[\d\s,]*$/',
            ],
            'health_percentage'         => 'int',
            'dangerous'                 => 'bool',
            'truesight'                 => 'bool',
            'bursting'                  => 'bool',
            'bolstering'                => 'bool',
            'sanguine'                  => 'bool',
            'runs_away_in_fear'         => 'bool',
            'bolstering_whitelist_npcs' => 'array',
            'spells'                    => 'array',
        ];
    }
}
