<?php

namespace App\Http\Requests\Heatmap;

use App\Http\Requests\DungeonRoute\DungeonRouteBaseUrlFormRequest;
use Illuminate\Validation\Rule;

/**
 * All options that a user can pass to the explore embed URL to generate a heatmap in an iframe.
 */
class ExploreEmbedUrlFormRequest extends ExploreUrlFormRequest
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
            'style'                 => ['nullable', Rule::in(['compact'])],
            'headerBackgroundColor' => ['nullable', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'mapBackgroundColor'    => ['nullable', 'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'],
            'showEnemyInfo'         => 'nullable|bool',
            'showTitle'             => 'nullable|bool',
        ]);
    }
}
