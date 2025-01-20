<?php

namespace App\Http\Requests\Api\V1\CombatLog\Route;

use App\Http\Models\Request\CombatLog\Route\CombatLogRouteRequestModel;
use App\Http\Requests\Api\V1\APIFormRequest;
use App\Models\Affix;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Rules\CombatLogRouteNpcChronologicalRule;
use Illuminate\Validation\Rule;

/**
 * @method CombatLogRouteRequestModel|null getModel()
 */
class CombatLogRouteRequest extends APIFormRequest
{

    protected function getRequestModelClass(): string
    {
        return CombatLogRouteRequestModel::class;
    }

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
        $dateFormat = sprintf('date_format:"%s"', CombatLogRouteRequestModel::DATE_TIME_FORMAT);

        return [
            'metadata.runId'                => ['required', 'string'],
            'metadata.keystoneRunId'        => ['nullable', 'int'],
            'metadata.loggedRunId'          => ['nullable', 'int'], // @TODO make required after raider.io supports it
            'metadata.period'               => ['nullable', 'int'], // @TODO make required after raider.io supports it
            'metadata.season'               => ['nullable', 'string'], // @TODO make required after raider.io supports it
            'metadata.regionId'             => ['nullable', 'int'], // @TODO make required after raider.io supports it
            'metadata.realmType'            => ['nullable', 'string'], // @TODO make required after raider.io supports it
            'metadata.wowInstanceId'        => ['nullable', 'int'],
            'settings.temporary'            => ['nullable', 'bool'],
            'settings.debugIcons'           => ['nullable', 'bool'],
            'roster.numMembers'             => ['nullable', 'int'], // @TODO make required after raider.io supports it
            'roster.averageItemLevel'       => ['nullable', 'numeric'], // @TODO make required after raider.io supports it
            'roster.characterIds'           => ['nullable', 'array'], // @TODO make required after raider.io supports it
            'roster.characterIds.*'         => ['nullable', 'int'], // @TODO make required after raider.io supports it
            'roster.specIds'                => ['nullable', 'array'], // @TODO make required after raider.io supports it
            'roster.specIds.*'              => ['nullable', 'int'], // @TODO make required after raider.io supports it
            'roster.classIds'               => ['nullable', 'array'], // @TODO make required after raider.io supports it
            'roster.classIds.*'             => ['nullable', 'int'], // @TODO make required after raider.io supports it
            'challengeMode.start'           => ['required', $dateFormat],
            'challengeMode.end'             => ['required', $dateFormat],
            'challengeMode.durationMs'      => ['required', 'int'],
            'challengeMode.parTimeMs'       => ['nullable', 'int'], // @TODO make required after raider.io supports it
            'challengeMode.timerFraction'   => ['nullable', 'numeric'], // @TODO make required after raider.io supports it
            'challengeMode.success'         => ['nullable', 'bool'],
            'challengeMode.challengeModeId' => ['required', Rule::exists(Dungeon::class, 'challenge_mode_id')],
            'challengeMode.level'           => ['required', 'int'],
            'challengeMode.numDeaths'       => ['nullable', 'int'], // @TODO make required after raider.io supports it
            'challengeMode.affixes'         => ['required', 'array'],
            'challengeMode.affixes.*'       => ['required', 'integer'], // #1818 Rule::exists(Affix::class, 'affix_id')],
            'npcs'                          => ['required', 'array', new CombatLogRouteNpcChronologicalRule()],
            'npcs.*.npcId'                  => ['required', 'integer'], // #1818 Rule::exists('npcs', 'id')
            'npcs.*.spawnUid'               => ['required', 'string', 'max:10'],
            'npcs.*.engagedAt'              => ['required', $dateFormat],
            'npcs.*.diedAt'                 => ['required', $dateFormat],
            'npcs.*.coord.x'                => ['required', 'numeric'],
            'npcs.*.coord.y'                => ['required', 'numeric'],
            'npcs.*.coord.uiMapId'          => ['required', Rule::exists(Floor::class, 'ui_map_id')],
            'spells'                        => 'nullable|array',
            'spells.*.spellId'              => 'integer',
            'spells.*.playerUid'            => 'string|max:32',
            'spells.*.castAt'               => $dateFormat,
            'spells.*.coord.x'              => 'numeric',
            'spells.*.coord.y'              => 'numeric',
            'spells.*.coord.uiMapId'        => Rule::exists(Floor::class, 'ui_map_id'),
            'playerDeaths'                  => 'nullable|array', // @TODO make required after raider.io supports it
            'playerDeaths.*.characterId'    => 'integer', // @TODO make required after raider.io supports it
            'playerDeaths.*.classId'        => 'integer', // @TODO make required after raider.io supports it
            'playerDeaths.*.specId'         => 'integer', // @TODO make required after raider.io supports it
            'playerDeaths.*.itemLevel'      => 'numeric', // @TODO make required after raider.io supports it
            'playerDeaths.*.diedAt'         => $dateFormat, // @TODO make required after raider.io supports it
            'playerDeaths.*.coord.x'        => 'numeric', // @TODO make required after raider.io supports it
            'playerDeaths.*.coord.y'        => 'numeric', // @TODO make required after raider.io supports it
            'playerDeaths.*.coord.uiMapId'  => Rule::exists(Floor::class, 'ui_map_id'), // @TODO make required after raider.io supports it
        ];
    }
}
