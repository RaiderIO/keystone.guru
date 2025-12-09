<?php

namespace App\Http\Requests\DungeonRoute;

class DungeonRoutePreviewUrlFormRequest extends DungeonRouteBaseUrlFormRequest
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
            'secret' => [
                'nullable',
                'string',
            ],
        ]);
    }
}
