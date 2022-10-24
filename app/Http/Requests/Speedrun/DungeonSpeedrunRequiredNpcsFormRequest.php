<?php

namespace App\Http\Requests\Speedrun;

use App\Models\Floor;
use App\Models\Npc;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DungeonSpeedrunRequiredNpcsFormRequest extends FormRequest
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
    public function rules(): array
    {
        $npcIds             = Npc::whereIn('dungeon_id', [-1, $this->get('dungeon_id')])->get('id')->pluck('id')->toArray();
        $npcIdsWithNullable = array_merge(
            [-1],
            $npcIds
        );
        return [
            'floor_id' => Rule::in(Floor::all('id')->pluck('id')->toArray()),
            'npc_id'   => Rule::in($npcIds),
            'npc2_id'  => Rule::in($npcIdsWithNullable),
            'npc3_id'  => Rule::in($npcIdsWithNullable),
            'npc4_id'  => Rule::in($npcIdsWithNullable),
            'npc5_id'  => Rule::in($npcIdsWithNullable),
            'count'    => 'required|int',
        ];
    }
}
