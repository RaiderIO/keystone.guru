<?php

namespace App\Http\Requests\Speedrun;

use App\Models\Dungeon;
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
        return [
            'dungeon_id' => [Dungeon::all()->pluck('id')->toArray()],
            'npc_id'     => Rule::in(Npc::whereIn('dungeon_id', [-1, $this->get('dungeon_id')])->get('id')->pluck('id')->toArray()),
            'count'      => 'required|integer',
        ];
    }
}
