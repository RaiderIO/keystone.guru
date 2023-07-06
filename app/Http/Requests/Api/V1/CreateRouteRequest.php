<?php

namespace App\Http\Requests\Api\V1;

use App\Service\CombatLog\Models\CreateRoute\CreateRouteBody;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CreateRouteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $dateFormat = sprintf('date_format:"%s"', CreateRouteBody::DATE_TIME_FORMAT);

        return [
            'settings.temporary'       => ['nullable', 'bool'],
            'settings.debugIcons'      => ['nullable', 'bool'],
            'challengeMode.start'      => ['required', $dateFormat],
            'challengeMode.end'        => ['required', $dateFormat],
            'challengeMode.durationMs' => ['required', 'int'],
            'challengeMode.mapId'      => ['required', Rule::exists('dungeons', 'map_id')],
            'challengeMode.level'      => ['required', 'int'],
            'challengeMode.affixes'    => ['required', 'array'],
            'challengeMode.affixes.*'  => ['required', Rule::exists('affixes', 'affix_id')],
            'npcs'                     => ['required', 'array'],
            'npcs.*.npcId'             => ['required', 'integer'], // #1818 Rule::exists('npcs', 'id')
            'npcs.*.spawnUid'          => ['required', 'string', 'max:10'],
            'npcs.*.engagedAt'         => ['required', $dateFormat],
            'npcs.*.diedAt'            => ['required', $dateFormat],
            'npcs.*.coord.x'           => ['required', 'numeric'],
            'npcs.*.coord.y'           => ['required', 'numeric'],
            'npcs.*.coord.uiMapId'     => ['required', Rule::exists('floors', 'ui_map_id')],
            'spells'                   => 'nullable|array',
            'spells.*.spellId'         => Rule::exists('spells', 'id'),
            'spells.*.playerUid'       => 'string|max:32',
            'spells.*.castAt'          => $dateFormat,
            'spells.*.coord.x'         => 'numeric',
            'spells.*.coord.y'         => 'numeric',
            'spells.*.coord.uiMapId'   => Rule::exists('floors', 'ui_map_id'),
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'data'    => $validator->errors(),
        ]));
    }
}
