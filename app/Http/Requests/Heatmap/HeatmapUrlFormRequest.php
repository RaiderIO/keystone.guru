<?php

namespace App\Http\Requests\Heatmap;

use App\Http\Requests\DungeonRoute\DungeonRouteBaseUrlFormRequest;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\GameServerRegion;
use Illuminate\Validation\Rule;

/**
 * All options that a user can pass to the heatmap URL to generate a heatmap
 */
class HeatmapUrlFormRequest extends DungeonRouteBaseUrlFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    #[\Override]
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    #[\Override]
    public function rules(): array
    {
        // @formatter:off
        return array_merge(parent::rules(), [
            'type'            => ['nullable', Rule::in(CombatLogEventEventType::cases()), ],
            'dataType'        => ['nullable', Rule::in(CombatLogEventDataType::cases()), ],
            'region'          => ['nullable', Rule::exists(GameServerRegion::class, 'short'), ],
            'minMythicLevel'  => ['nullable', 'integer', ],
            'maxMythicLevel'  => ['nullable', 'integer', ],
            'minItemLevel'    => ['nullable', 'integer', ],
            'maxItemLevel'    => ['nullable', 'integer', ],
            'minPlayerDeaths' => ['nullable', 'integer', ],
            'maxPlayerDeaths' => ['nullable', 'integer', ],

            'includeAffixIds' => ['nullable', 'string', ], // csv
            'excludeAffixIds' => ['nullable', 'string', ], // csv
            'includeClassIds' => ['nullable', 'string', ], // csv
            'excludeClassIds' => ['nullable', 'string', ], // csv
            'includeSpecIds'  => ['nullable', 'string', ], // csv
            'excludeSpecIds'  => ['nullable', 'string', ], // csv

            'includePlayerDeathSpecIds'  => ['nullable', 'string', ], // csv
            'excludePlayerDeathSpecIds'  => ['nullable', 'string', ], // csv
            'includePlayerDeathClassIds' => ['nullable', 'string', ], // csv
            'excludePlayerDeathClassIds' => ['nullable', 'string', ], // csv

            'includePlayerSpellIds' => ['nullable', 'string', ], // csv

            'minPeriod'          => ['nullable', 'integer', ],
            'maxPeriod'          => ['nullable', 'integer', ],
            'minTimerFraction'   => ['nullable', 'numeric', ],
            'maxTimerFraction'   => ['nullable', 'numeric', ],
            'minSamplesRequired' => ['nullable', 'integer', ],
            'token'              => ['nullable', 'string', ],
        ]);
        // @formatter:on
    }
}
