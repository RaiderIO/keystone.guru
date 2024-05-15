<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Affix;
use App\Models\Dungeon;
use App\Models\Floor\Floor;
use App\Models\Spell;
use App\Rules\CreateRouteNpcChronologicalRule;
use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use Illuminate\Validation\Rule;

class CreateRouteRequest extends APIFormRequest
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
        $dateFormat = sprintf('date_format:"%s"', CreateRouteBody::DATE_TIME_FORMAT);

        return [
            'metadata.runId'                => ['required', 'string'],
            'settings.temporary'            => ['nullable', 'bool'],
            'settings.debugIcons'           => ['nullable', 'bool'],
            'challengeMode.start'           => ['required', $dateFormat],
            'challengeMode.end'             => ['required', $dateFormat],
            'challengeMode.durationMs'      => ['required', 'int'],
            'challengeMode.success'         => ['nullable', 'bool'],
            'challengeMode.challengeModeId' => ['required', Rule::exists(Dungeon::class, 'challenge_mode_id')],
            'challengeMode.level'           => ['required', 'int'],
            'challengeMode.affixes'         => ['required', 'array'],
            'challengeMode.affixes.*'       => ['required', Rule::exists(Affix::class, 'affix_id')],
            'npcs'                          => ['required', 'array', new CreateRouteNpcChronologicalRule()],
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
        ];
    }
}
