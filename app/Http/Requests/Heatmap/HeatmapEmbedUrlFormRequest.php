<?php

namespace App\Http\Requests\Heatmap;

use Illuminate\Validation\Rule;

/**
 * All options that a user can pass to the explore embed URL to generate a heatmap in an iframe.
 */
class HeatmapEmbedUrlFormRequest extends ExploreUrlFormRequest
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
                Rule::in(['compact']),
            ],
            'headerBackgroundColor' => [
                'nullable',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'mapBackgroundColor' => [
                'nullable',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'showEnemyInfo' => 'nullable|bool',
            'showTitle'     => 'nullable|bool',
            'showSidebar'   => 'nullable|bool',
            'defaultZoom'   => 'nullable|numeric',
        ]);
    }
}
