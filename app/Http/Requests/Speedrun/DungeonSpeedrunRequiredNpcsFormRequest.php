<?php

namespace App\Http\Requests\Speedrun;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Laratrust\Role;
use App\Models\Npc\Npc;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DungeonSpeedrunRequiredNpcsFormRequest extends FormRequest
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
        $npcIds = Npc::join('npc_dungeons', 'npc_dungeons.npc_id', '=', 'npcs.id')
            ->select('npcs.id')
            ->where('npc_dungeons.dungeon_id', $this->get('dungeon_id'))
            ->get(['id'])
            ->toArray();

        $npcIdsWithNullable = array_merge($npcIds, [-1]);

        return [
            'floor_id'   => Rule::in(Floor::all('id')->pluck('id')->toArray()),
            'npc_id'     => Rule::in($npcIds),
            'npc2_id'    => Rule::in($npcIdsWithNullable),
            'npc3_id'    => Rule::in($npcIdsWithNullable),
            'npc4_id'    => Rule::in($npcIdsWithNullable),
            'npc5_id'    => Rule::in($npcIdsWithNullable),
            'difficulty' => Rule::in(Dungeon::DIFFICULTY_ALL),
            'count'      => 'required|int',
        ];
    }
}
