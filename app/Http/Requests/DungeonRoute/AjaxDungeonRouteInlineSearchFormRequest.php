<?php

namespace App\Http\Requests\DungeonRoute;

/**
 * All options that a user can pass to search for a dungeon route on the map view
 */
class AjaxDungeonRouteInlineSearchFormRequest extends DungeonRouteBaseUrlFormRequest
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
        // @formatter:off
        return array_merge(parent::rules(), [
            'offset'      => 'integer|required',
            'limit'       => 'integer|required',
            'title'       => 'string',
            'username'    => 'string',
            'minKeyLevel' => ['nullable', 'integer', ],
            'maxKeyLevel' => ['nullable', 'integer', ],
        ]);
        // @formatter:on
    }
}
