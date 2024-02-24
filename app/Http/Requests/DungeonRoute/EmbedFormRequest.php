<?php

namespace App\Http\Requests\DungeonRoute;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmbedFormRequest extends FormRequest
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
        return [
            'style'                 => ['nullable', Rule::in(['compact', 'regular'])],
            'pullsDefaultState'     => 'nullable|integer',
            'pullsHideOnMove'       => 'nullable|bool',
            'headerBackgroundColor' => ['nullable', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'mapBackgroundColor'    => ['nullable', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'showEnemyInfo'         => 'nullable|bool',
            'showPulls'             => 'nullable|bool',
            'showEnemyForces'       => 'nullable|bool',
            'showAffixes'           => 'nullable|bool',
            'showTitle'             => 'nullable|bool',
            'showPresenterButton'   => 'nullable|bool',
        ];
    }
}
