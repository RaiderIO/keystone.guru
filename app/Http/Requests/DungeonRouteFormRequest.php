<?php

namespace App\Http\Requests;

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
            'dungeon_route_title' => 'required|string|min:3|max:80',
            // Only active dungeons are allowed
            'dungeon_id' => ['required', Rule::exists('dungeons', 'id')->where('active', '1')],
            // 'difficulty' => ['required', Rule::in(config('keystoneguru.dungeonroute_difficulty'))],
            'teeming' => 'nullable|int',
            'template' => 'nullable|int',
            'seasonal_index' => 'int',
            'keystone_level' => 'int|min:2|max:30',

            'faction_id' => ['required', Rule::exists('factions', 'id'), new SiegeOfBoralusFactionRule($this->request)],

            'race' => 'nullable|array',
            'class' => 'nullable|array',

            'race.*' => 'nullable|numeric',
            'class.*' => 'nullable|numeric',

            'affixes' => 'required|array',
            'affixes.*' => 'required|numeric',
            'attributes.*' => 'nullable|numeric',

            'unlisted' => 'nullable|int',
        ];

        // Validate demo state, optional or numeric
        if (Auth::user()->hasRole('admin')) {
            $rules['demo'] = 'numeric';
        }

        return $rules;
    }
}
