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
            // The factor the killzone-path (pull-connection) line weight is multiplied by for this render, so
            // a small miniature still reads as a route shape. Absent/null keeps the map's normal line width
            // (e.g. the large hero variant).
            'killzonepathweight' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:1',
                'max:10',
            ],
        ]);
    }
}
