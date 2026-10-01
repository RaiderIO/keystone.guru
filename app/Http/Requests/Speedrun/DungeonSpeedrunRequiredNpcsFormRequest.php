<?php

namespace App\Http\Requests\Speedrun;

use App\Models\Dungeon;
use App\Models\DungeonDifficulty;
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
    }    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        /** @var Dungeon $dungeon */
        $dungeon = $this->route('dungeon');
        /** @var Floor $floor */
        $floor = $this->route('floor');

        $npcIds = Npc::join('npc_dungeons', 'npc_dungeons.npc_id', '=', 'npcs.id')
            ->select('npcs.id')
            ->where('npc_dungeons.dungeon_id', $dungeon->id)
            ->pluck('id')
            ->toArray();

        $npcIdsWithNullable = array_merge($npcIds, [-1]);

        return [
            'floor_id'   => ['required', Rule::in([$floor->id])],
            'npc_id'     => ['required', Rule::in($npcIds)],
            'npc2_id'    => ['required', Rule::in($npcIdsWithNullable)],
            'npc3_id'    => ['required', Rule::in($npcIdsWithNullable)],
            'npc4_id'    => ['required', Rule::in($npcIdsWithNullable)],
            'npc5_id'    => ['required', Rule::in($npcIdsWithNullable)],
            'difficulty' => ['required', Rule::in(DungeonDifficulty::values())],
            'count'      => 'required|int',
        ];
    }
}
