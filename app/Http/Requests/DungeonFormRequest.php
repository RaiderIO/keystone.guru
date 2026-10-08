<?php

namespace App\Http\Requests;

use App\Models\Dungeon;
use App\Models\DungeonDifficulty;
use App\Models\Laratrust\Role;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ConditionalRules;
use Illuminate\Validation\Rule;

class DungeonFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }    /**
     * @return array<string, array<int, string|Rule|ConditionalRules>|string|Rule>
     */
    public function rules(): array
    {
        return [
            'active'                  => 'nullable|boolean',
            'has_wallpaper'           => 'nullable|boolean',
            'raid'                    => 'nullable|boolean',
            'heatmap_enabled'         => 'nullable|boolean',
            'speedrun_enabled'        => 'nullable|boolean',
            'speedrun_difficulties'   => 'nullable|array',
            'speedrun_difficulties.*' => ['integer', Rule::in(DungeonDifficulty::values())],
            'zone_id'                 => 'int',
            'map_id'                  => 'int',
            'instance_id'             => 'nullable|int',
            'challenge_mode_id'       => 'nullable|int',
            'mdt_id'                  => 'int',
            'name'                    => [
                'required',
                Rule::unique(Dungeon::class, 'name')->ignore($this->get('name'), 'name'),
            ],
            'abbreviation' => [
                'required',
                Rule::unique(Dungeon::class, 'abbreviation')->ignore($this->get('abbreviation'), 'abbreviation'),
            ],
            'key' => [
                'required',
                Rule::unique(Dungeon::class, 'key')->ignore($this->get('key'), 'key'),
                Rule::in(Dungeon::allKeys()),
            ],
            'slug' => [
                'required',
                Rule::unique(Dungeon::class, 'slug')->ignore($this->get('slug'), 'slug'),
            ],
            'min_suggested_level' => ['nullable', 'integer', 'min:1', 'max:255'],
            'max_suggested_level' => [
                'nullable',
                'integer',
                'min:1',
                'max:255',
                Rule::when($this->filled('min_suggested_level'), ['gte:min_suggested_level']),
            ],
        ];
    }
}
