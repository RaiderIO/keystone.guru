<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\Dungeon;
use App\Models\Expansion;
use App\Rules\SiegeOfBoralusFactionRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DungeonRouteFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Auth::user()->hasRole(["user", "admin"]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'dungeon_route_title'       => 'nullable|string|max:80',
            'dungeon_route_description' => 'nullable|string|max:1000',
            'dungeon_route_sandbox'     => 'int',
            'level_min'                 => sprintf('int|min:%d|max:%d', config('keystoneguru.levels.min'), config('keystoneguru.levels.max')),
            'level_max'                 => sprintf('int|min:%d|max:%d', config('keystoneguru.levels.min'), config('keystoneguru.levels.max')),
            // Only active dungeons are allowed
            'dungeon_id'                => ['required', Rule::in(
                Dungeon::select('dungeons.id')
                    ->join('expansions', 'dungeons.expansion_id', '=', 'expansions.id')
                    ->where('expansions.active', true)
                    ->where('dungeons.active', true)
                    ->get()
                    ->pluck('id')
                    ->toArray()
            )],
            // May be -1 (unset) or must be part of the user's teams
            'team_id'                   => [Rule::in(
                array_merge(Auth::check() ? Auth::user()->teams->pluck('id')->toArray() : [], [-1])
            )],
            // 'difficulty' => ['required', Rule::in(config('keystoneguru.dungeonroute_difficulty'))],
            'teeming'                   => 'nullable|int',
            'template'                  => 'nullable|int',

            // Array since there's potentially a seasonal index per expansion
            'seasonal_index'   => 'nullable|array',
            'seasonal_index.*' => 'nullable|numeric',

            'faction_id' => [Rule::exists('factions', 'id'), new SiegeOfBoralusFactionRule($this->request)],

            'race'  => 'nullable|array',
            'class' => 'nullable|array',

            'race.*'  => 'nullable|numeric',
            'class.*' => 'nullable|numeric',

            'route_select_affixes'   => 'array',
            'route_select_affixes.*' => 'string',
            'attributes.*'           => 'nullable|numeric',

            'unlisted' => 'nullable|int',
        ];

        // Validate demo state, optional or numeric
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            $rules['demo'] = 'numeric';
        }

        return $rules;
    }
}
