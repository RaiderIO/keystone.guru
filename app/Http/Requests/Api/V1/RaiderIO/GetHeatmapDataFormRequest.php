<?php

namespace App\Http\Requests\Api\V1\RaiderIO;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetHeatmapDataFormRequest extends FormRequest
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
        return [
            'dungeon_id'      => ['required', Rule::exists(Dungeon::class, 'id')],
            'event_type'      => ['required', Rule::in(CombatLogEventEventType::cases())],
            'data_type' => ['required', Rule::in(CombatLogEventDataType::cases())],
            'level'           => ['nullable', 'regex:/^\d*;\d*$/'],
            'affixes'         => ['nullable', 'array'],
            'affixes.*'       => ['integer', Rule::exists(Affix::class, 'id')],
            'affix_groups'    => ['nullable', 'array'],
            'affix_groups.*'  => ['integer', Rule::exists(AffixGroup::class, 'id')],
            'date_range_from' => ['nullable', 'date_format:Y-m-d'],
            'date_range_to'   => ['nullable', 'date_format:Y-m-d'],
            'duration'        => ['nullable', 'regex:/^\d*;\d*$/'],
        ];
    }
}
