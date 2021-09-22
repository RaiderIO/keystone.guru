<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\Dungeon;
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
            // 'difficulty' => ['required', Rule::in(config('keystoneguru.dungeonroute_difficulty'))],
            'teeming'                   => 'nullable|int',
            'template'                  => 'nullable|int',
            'seasonal_index'            => 'int',

            'faction_id' => [Rule::exists('factions', 'id'), new SiegeOfBoralusFactionRule($this->request)],

            'race'  => 'nullable|array',
            'class' => 'nullable|array',

            'race.*'  => 'nullable|numeric',
            'class.*' => 'nullable|numeric',

            'affixes'      => 'array',
            'affixes.*'    => 'numeric',
            'attributes.*' => 'nullable|numeric',

            'unlisted' => 'nullable|int',
        ];

        // Validate demo state, optional or numeric
        if (Auth::check() && Auth::user()->hasRole('admin')) {
            $rules['demo'] = 'numeric';
        }

        return $rules;
    }
}
