<?php

namespace App\Http\Requests\DungeonRoute;

use Override;

class DungeonRoutePreviewUrlFormRequest extends DungeonRouteBaseUrlFormRequest
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
            'secret' => [
                'nullable',
                'string',
            ],
            // Whether the pull-connection lines should be thickened for a miniature render; the larger
            // hero variant passes 0 so its lines keep the normal width.
            'thicklines' => [
                'sometimes',
                'nullable',
                'boolean',
            ],
        ]);
    }
}
