<?php

namespace App\Http\Requests\Heatmap;

use App\Http\Requests\DungeonRoute\DungeonRouteBaseUrlFormRequest;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use App\Models\GameServerRegion;
use App\Service\RaiderIO\Dtos\HeatmapDataFilter;
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
            'minLevel'        => ['nullable', 'integer', sprintf('between:%d,%d', config('keystoneguru.heatmap.continent.level_min'), config('keystoneguru.heatmap.continent.level_max')), ],
            'maxLevel'        => ['nullable', 'integer', sprintf('between:%d,%d', config('keystoneguru.heatmap.continent.level_min'), config('keystoneguru.heatmap.continent.level_max')), ],
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
        ], $this->getDungeonKindRules());
        // @formatter:on
    }

    /**
     * The dungeon the heatmap is shown for, or null when the route carries none.
     */
    public function getHeatmapDungeon(): ?Dungeon
    {
        $dungeon = $this->route('dungeon');

        return $dungeon instanceof Dungeon ? $dungeon : null;
    }

    /**
     * Rules that replace the regular ones for the kind of dungeon the heatmap is shown for: the parameters outside its
     * filter set are excluded from the validated data, so neither a redirect nor an embed carries them along. A
     * continent also limits the event type to the death types. Merge these last, after every other rule.
     *
     * @return array<string, array<int, string|Rule>>
     */
    protected function getDungeonKindRules(): array
    {
        $isContinent = $this->getHeatmapDungeon()?->isContinent() === true;

        $rules = $isContinent ? [
            'type' => ['nullable', Rule::in(HeatmapDataFilter::CONTINENT_EVENT_TYPES), ],
        ] : [];

        $unsupportedParameters = $isContinent ?
            HeatmapDataFilter::CONTINENT_UNSUPPORTED_PARAMETERS :
            HeatmapDataFilter::DUNGEON_UNSUPPORTED_PARAMETERS;
        foreach ($unsupportedParameters as $parameter) {
            $rules[$parameter] = ['exclude', ];
        }

        return $rules;
    }
}
