<?php

namespace App\Http\Requests;

use App\Models\Dungeon;
use App\Models\GameVersion\GameVersion;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DungeonFormRequest extends FormRequest
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
        return [
            'active'            => 'nullable|boolean',
            'speedrun_enabled'  => 'nullable|boolean',
            'facade_enabled'    => 'nullable|boolean',
            'game_version_id'   => Rule::exists(GameVersion::class, 'id'),
            'zone_id'           => 'int',
            'map_id'            => 'int',
            'challenge_mode_id' => 'int',
            'mdt_id'            => 'int',
            'name'              => ['required', Rule::unique(Dungeon::class, 'name')->ignore($this->get('name'), 'name')],
            'key'               => [
                'required',
                Rule::unique(Dungeon::class, 'key')->ignore($this->get('key'), 'key'),
                Rule::in(collect(array_merge(Dungeon::ALL, Dungeon::ALL_RAID))->flatten()),
            ],
            'slug'              => ['required', Rule::unique(Dungeon::class, 'slug')->ignore($this->get('slug'), 'slug')],
        ];
    }
}
