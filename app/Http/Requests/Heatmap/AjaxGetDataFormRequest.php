<?php

namespace App\Http\Requests\Heatmap;

use App\Models\Affix;
use App\Models\AffixGroup\AffixGroup;
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
            'dungeon_id'     => ['required', Rule::exists(Dungeon::class, 'id')],
            'level'          => ['nullable', 'regex:/^\d*;\d*$/',],
            'affixes'        => ['nullable', 'array'],
            'affixes.*'      => ['integer', Rule::exists(Affix::class, 'id')],
            'affix_groups'   => ['nullable', 'array'],
            'affix_groups.*' => ['integer', Rule::exists(AffixGroup::class, 'id')],
        ];
    }
}

