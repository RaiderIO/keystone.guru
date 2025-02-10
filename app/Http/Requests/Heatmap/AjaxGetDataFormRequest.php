<?php

namespace App\Http\Requests\Heatmap;

use App\Models\Affix;
use App\Models\CharacterClass;
use App\Models\CharacterClassSpecialization;
use App\Models\Dungeon;
use Illuminate\Validation\Rule;

/**
 * Used when the heatmaps request data from the backend.
 */
class AjaxGetDataFormRequest extends ExploreUrlFormRequest
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
        return array_merge(parent::rules(), [
            'dungeonId'                    => ['required', Rule::exists(Dungeon::class, 'id')],
            // These are overrides since it's easier to split the csv as an array for this endpoint
            'includeAffixIds'              => ['nullable', 'array'],
            'includeAffixIds.*'            => ['integer', Rule::exists(Affix::class, 'affix_id')],
            'includeClassIds'              => ['nullable', 'array'],
            'includeClassIds.*'            => ['integer', Rule::exists(CharacterClass::class, 'class_id')],
            'includeSpecIds'               => ['nullable', 'array'],
            'includeSpecIds.*'             => ['integer', Rule::exists(CharacterClassSpecialization::class, 'specialization_id')],
            'includePlayerDeathClassIds'   => ['nullable', 'array'],
            'includePlayerDeathClassIds.*' => ['integer', Rule::exists(CharacterClass::class, 'class_id')],
            'includePlayerDeathSpecIds'    => ['nullable', 'array'],
            'includePlayerDeathSpecIds.*'  => ['integer', Rule::exists(CharacterClassSpecialization::class, 'specialization_id')],
        ]);
    }
}

