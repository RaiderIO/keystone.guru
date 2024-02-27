<?php

namespace App\Http\Requests\Speedrun;

use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Npc;
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
        return Auth::user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $npcIds = Npc::whereIn('dungeon_id', [-1, $this->get('dungeon_id')])
            ->get('id')
            ->pluck('id')
            ->toArray();

        $npcIdsWithNullable = array_merge($npcIds, [-1]);

        return [
            'floor_id' => Rule::in(Floor::all('id')->pluck('id')->toArray()),
            'npc_id' => Rule::in($npcIds),
            'npc2_id' => Rule::in($npcIdsWithNullable),
            'npc3_id' => Rule::in($npcIdsWithNullable),
            'npc4_id' => Rule::in($npcIdsWithNullable),
            'npc5_id' => Rule::in($npcIdsWithNullable),
            'difficulty' => Rule::in(Dungeon::DIFFICULTY_ALL),
            'count' => 'required|int',
        ];
    }
}
