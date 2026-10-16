<?php

namespace App\Http\Requests\Api\V1\CombatLog\Route;

use App\Dto\Request\CombatLog\Route\CombatLogRouteRequestDto;
use App\Http\Requests\Api\V1\APIFormRequest;
use App\Models\Dungeon;
use App\Rules\CombatLogRouteNpcChronologicalRule;
use Illuminate\Validation\Rule;

/**
 * @method CombatLogRouteRequestDto|null getDto()
 */
class CombatLogRouteRequest extends APIFormRequest
{
    protected function getRequestDtoClass(): string
    {
        return CombatLogRouteRequestDto::class;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $dateFormat = sprintf('date_format:"%s"', CombatLogRouteRequestDto::DATE_TIME_FORMAT);

        return [
            'metadata.runId' => [
                'required',
                'string',
            ],
            'metadata.keystoneRunId' => [
                'nullable',
                'int',
            ],
            'metadata.loggedRunId' => [
                'nullable',
                'int',
            ],
            'metadata.period' => [
                'required',
                'int',
            ],
            'metadata.season' => [
                'required',
                'string',
            ],
            'metadata.regionId' => [
                'required',
                'int',
            ],
            'metadata.realmType' => [
                'required',
                'string',
            ],
            'metadata.wowInstanceId' => [
                'present',
                'nullable',
                'int',
            ],
            'settings.temporary' => [
                'nullable',
                'bool',
            ],
            'settings.debugIcons' => [
                'nullable',
                'bool',
            ],
            'settings.mappingVersion' => [
                'nullable',
                'numeric',
            ],
            'settings.publicKey' => [
                'nullable',
                'string',
            ],
            'roster.numMembers' => [
                'nullable',
                'int',
            ],
            'roster.averageItemLevel' => [
                'present',
                'nullable',
                'numeric',
            ],
            'roster.characterIds' => [
                'present',
                'array',
            ],
            'roster.characterIds.*' => [
                'required',
                'int',
            ],
            'roster.specIds' => [
                'present',
                'array',
            ],
            'roster.specIds.*' => [
                'required',
                'int',
            ],
            'roster.classIds' => [
                'present',
                'array',
            ],
            'roster.classIds.*' => [
                'required',
                'int',
            ],
            'challengeMode.start' => [
                'required',
                $dateFormat,
            ],
            'challengeMode.end' => [
                'required',
                $dateFormat,
            ],
            'challengeMode.durationMs' => [
                'required',
                'int',
            ],
            'challengeMode.parTimeMs' => [
                'nullable',
                'int',
            ],
            'challengeMode.timerFraction' => [
                'required',
                'numeric',
            ],
            'challengeMode.success' => [
                'present',
                'nullable',
                'bool',
            ],
            'challengeMode.challengeModeId' => [
                'required',
                Rule::exists(Dungeon::class, 'challenge_mode_id'),
            ],
            'challengeMode.level' => [
                'required',
                'int',
            ],
            'challengeMode.numDeaths' => [
                'nullable',
                'int',
            ],
            'challengeMode.affixes' => [
                'present',
                'nullable',
                'array',
            ],
            'challengeMode.affixes.*' => [
                'nullable',
                'integer',
            ],
            // #1818 Rule::exists(Affix::class, 'affix_id')],
            'npcs' => [
                'required',
                'array',
                new CombatLogRouteNpcChronologicalRule(),
            ],
            'npcs.*.npcId' => [
                'required',
                'integer',
            ],
            // #1818 Rule::exists('npcs', 'id')
            'npcs.*.spawnUid' => [
                'required',
                'string',
                'max:10',
            ],
            'npcs.*.engagedAt' => [
                'required',
                $dateFormat,
            ],
            'npcs.*.diedAt' => [
                'required',
                $dateFormat,
            ],
            'npcs.*.coord.x' => [
                'required',
                'numeric',
            ],
            'npcs.*.coord.y' => [
                'required',
                'numeric',
            ],
            'npcs.*.coord.uiMapId' => [
                'required',
                'integer',
            ],
            // 'Rule::exists(Floor::class, 'ui_map_id')'],
            'spells'                 => 'nullable|array',
            'spells.*.spellId'       => 'integer',
            'spells.*.playerUid'     => 'string|max:32',
            'spells.*.castAt'        => $dateFormat,
            'spells.*.coord.x'       => 'numeric',
            'spells.*.coord.y'       => 'numeric',
            'spells.*.coord.uiMapId' => 'integer',
            // 'Rule::exists(Floor::class, 'ui_map_id')',
            'playerDeaths'                 => 'nullable|array',
            'playerDeaths.*.characterId'   => 'required|integer',
            'playerDeaths.*.classId'       => 'required|integer',
            'playerDeaths.*.specId'        => 'required|integer',
            'playerDeaths.*.itemLevel'     => 'required|numeric',
            'playerDeaths.*.diedAt'        => ['required', $dateFormat],
            'playerDeaths.*.coord.x'       => 'required|numeric',
            'playerDeaths.*.coord.y'       => 'required|numeric',
            'playerDeaths.*.coord.uiMapId' => 'required|integer',
            // 'Rule::exists(Floor::class, 'ui_map_id')',
        ];
    }
}
