<?php

namespace App\Http\Requests\DungeonRoute;

use Illuminate\Validation\Rule;

class DungeonRouteEmbedUrlFormRequest extends DungeonRouteBaseUrlFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    #[\Override]
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'style' => [
                'nullable',
                Rule::in([
                    'compact',
                    'regular',
                ]),
            ],
            'pullsDefaultState'     => 'nullable|integer',
            'pullsHideOnMove'       => 'nullable|bool',
            'headerBackgroundColor' => [
                'nullable',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'mapBackgroundColor' => [
                'nullable',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'showEnemyInfo'       => 'nullable|bool',
            'showPulls'           => 'nullable|bool',
            'showEnemyForces'     => 'nullable|bool',
            'showAffixes'         => 'nullable|bool',
            'showTitle'           => 'nullable|bool',
            'showPresenterButton' => 'nullable|bool',
            'showHeader'          => 'nullable|bool',
        ]);
    }
}
