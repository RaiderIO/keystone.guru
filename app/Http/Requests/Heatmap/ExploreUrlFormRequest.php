<?php

namespace App\Http\Requests\Heatmap;

use App\Http\Requests\DungeonRoute\DungeonRouteBaseUrlFormRequest;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use Illuminate\Validation\Rule;

/**
 * All options that a user can pass to the explore URL to generate a heatmap
 */
class ExploreUrlFormRequest extends DungeonRouteBaseUrlFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'type'             => ['nullable', Rule::in(CombatLogEventEventType::cases())],
            'dataType'         => ['nullable', Rule::in(CombatLogEventDataType::cases())],
            'minMythicLevel'   => ['nullable', 'integer'],
            'maxMythicLevel'   => ['nullable', 'integer'],
            'minItemLevel'     => ['nullable', 'integer'],
            'maxItemLevel'     => ['nullable', 'integer'],
            'minPlayerDeaths'  => ['nullable', 'integer'],
            'maxPlayerDeaths'  => ['nullable', 'integer'],
            'includeAffixIds'  => ['nullable', 'string'],
            'minPeriod'        => ['nullable', 'integer'],
            'maxPeriod'        => ['nullable', 'integer'],
            'minTimerFraction' => ['nullable', 'numeric'],
            'maxTimerFraction' => ['nullable', 'numeric'],
        ]);
    }
}
