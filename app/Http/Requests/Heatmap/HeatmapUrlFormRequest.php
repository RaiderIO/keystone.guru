<?php

namespace App\Http\Requests\Heatmap;

use App\Http\Requests\DungeonRoute\DungeonRouteBaseUrlFormRequest;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\GameServerRegion;
use Illuminate\Validation\Rule;
use Override;

/**
 * All options that a user can pass to the heatmap URL to generate a heatmap
 */
class HeatmapUrlFormRequest extends DungeonRouteBaseUrlFormRequest
{
    private const string CSV_IDS_REGEX = 'regex:/^\\d+(,\\d+)*$/';

    /** Raider.IO hands out the token as base64-encoded JSON (standard or URL-safe alphabet). */
    private const string TOKEN_REGEX = 'regex:/^[A-Za-z0-9+\\/_-]+={0,2}$/';

    /**
     * Determine if the user is authorized to make this request.
     */
    #[Override]
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    #[Override]
    /**

     * @return array<string, array<int, string|Rule>|string|Rule>
     */

    public function rules(): array
    {
        // @formatter:off
        return array_merge(parent::rules(), [
            'type'            => ['nullable', Rule::in(CombatLogEventEventType::cases()), ],
            'dataType'        => ['nullable', Rule::in(CombatLogEventDataType::cases()), ],
            'region'          => ['nullable', Rule::exists(GameServerRegion::class, 'key'), ],
            'minMythicLevel'  => ['nullable', 'integer', ],
            'maxMythicLevel'  => ['nullable', 'integer', ],
            'minItemLevel'    => ['nullable', 'integer', ],
            'maxItemLevel'    => ['nullable', 'integer', ],
            'minPlayerDeaths' => ['nullable', 'integer', ],
            'maxPlayerDeaths' => ['nullable', 'integer', ],

            'includeAffixIds' => ['nullable', 'string', ], // csv
            'excludeAffixIds' => ['nullable', 'string', self::CSV_IDS_REGEX, ], // csv
            'includeClassIds' => ['nullable', 'string', ], // csv
            'excludeClassIds' => ['nullable', 'string', self::CSV_IDS_REGEX, ], // csv
            'includeSpecIds'  => ['nullable', 'string', ], // csv
            'excludeSpecIds'  => ['nullable', 'string', self::CSV_IDS_REGEX, ], // csv

            'includePlayerDeathSpecIds'  => ['nullable', 'string', ], // csv
            'excludePlayerDeathSpecIds'  => ['nullable', 'string', self::CSV_IDS_REGEX, ], // csv
            'includePlayerDeathClassIds' => ['nullable', 'string', ], // csv
            'excludePlayerDeathClassIds' => ['nullable', 'string', self::CSV_IDS_REGEX, ], // csv

            'includePlayerSpellIds' => ['nullable', 'string', ], // csv

            'minPeriod'          => ['nullable', 'integer', ],
            'maxPeriod'          => ['nullable', 'integer', ],
            'minTimerFraction'   => ['nullable', 'numeric', ],
            'maxTimerFraction'   => ['nullable', 'numeric', ],
            'minSamplesRequired' => ['nullable', 'integer', ],
            'token'              => ['nullable', 'string', 'max:4096', self::TOKEN_REGEX, ],
            'season'             => ['nullable', 'string', 'regex:/^season-[a-z]+-\d$/i', ],
        ]);
        // @formatter:on
    }
}
