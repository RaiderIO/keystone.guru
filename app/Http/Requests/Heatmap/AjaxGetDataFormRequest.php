<?php

namespace App\Http\Requests\Heatmap;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
use App\Models\CombatLog\CombatLogEventDataType;
use App\Models\CombatLog\CombatLogEventEventType;
use App\Models\Dungeon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AjaxGetDataFormRequest extends FormRequest
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
            'dungeonId'        => ['required', Rule::exists(Dungeon::class, 'id')],
            'type'             => ['required', Rule::in(CombatLogEventEventType::cases())],
            'dataType'         => ['required', Rule::in(CombatLogEventDataType::cases())],
            'minMythicLevel'   => ['nullable', 'integer'],
            'maxMythicLevel'   => ['nullable', 'integer'],
            'affixes'          => ['nullable', 'array'],
            'affixes.*'        => ['integer', Rule::exists(Affix::class, 'id')],
            'minPeriod'        => ['nullable', 'integer'],
            'maxPeriod'        => ['nullable', 'integer'],
            'minTimerFraction' => ['nullable', 'numeric'],
            'maxTimerFraction' => ['nullable', 'numeric'],
        ];
    }
}

