<?php

namespace App\Http\Requests\DungeonRoute;

use App\Models\User;
use Illuminate\Validation\Rule;
use Override;

class DungeonRouteEmbedUrlFormRequest extends DungeonRouteBaseUrlFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    #[Override]
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    #[Override]
    /**

     * @return array<string, array<int, string|Rule>|string|Rule>
     */

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
            'mapFacadeStyle' => [
                'nullable',
                Rule::in(User::MAP_FACADE_STYLE_ALL),
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
