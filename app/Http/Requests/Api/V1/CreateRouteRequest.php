<?php

namespace App\Http\Requests\Api\V1;

use App\Logic\CombatLog\CombatLogEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
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
        $dateFormat = sprintf('date_format:"%s"', CombatLogEntry::DATE_FORMAT);
        return [
            'challengemode.start'      => $dateFormat,
            'challengemode.end'        => $dateFormat,
            'challengemode.durationMs' => 'int',
            'challengemode.zoneId'     => Rule::exists('dungeons', 'zone_id'),
            'challengemode.level'      => 'int',
            'challengemode.affixes'    => 'array',
            'challengemode.affixes.*'  => Rule::exists('affixes', 'affix_id'),
            'npcs'                     => 'array',
            'npcs.npcId'               => Rule::exists('npcs', 'id'),
            'npcs.spawnUid'            => 'string|max:10',
            'npcs.engagedAt'           => $dateFormat,
            'npcs.diedAt'              => $dateFormat,
            'npcs.coord.x'             => 'float',
            'npcs.coord.y'             => 'float',
            'npcs.coord.uiMapId'       => Rule::exists('floors', 'ui_map_id'),
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
