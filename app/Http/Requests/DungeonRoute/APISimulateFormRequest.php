<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\SimulationCraft\SimulationCraftRaidEventsOptions;
use Illuminate\Validation\Rule;

class APISimulateFormRequest extends DungeonRouteFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'key_level'                      => 'required|int|max:40',
            'shrouded_bounty_type'           => ['required', Rule::in(
                SimulationCraftRaidEventsOptions::ALL_SHROUDED_BOUNTY_TYPES
            )],
            'affix'                          => ['required', Rule::in(
                SimulationCraftRaidEventsOptions::ALL_AFFIXES
            )],
            'thundering'                     => 'required|in:0,1',
            'bloodlust'                      => 'required|in:0,1',
            'arcane_intellect'               => 'required|in:0,1',
            'power_word_fortitude'           => 'required|in:0,1',
            'battle_shout'                   => 'required|in:0,1',
            'mystic_touch'                   => 'required|in:0,1',
            'chaos_brand'                    => 'required|in:0,1',
            'hp_percent'                     => 'required|int',
            'ranged_pull_compensation_yards' => 'required|int',
            'use_mounts'                     => 'in:0,1',
            'simulate_bloodlust_per_pull'    => 'array',
            'simulate_bloodlust_per_pull.*'  => 'int',
        ];
    }
}
