<?php

namespace App\Http\Requests\DungeonRoute;

use Override;

class AjaxDungeonRouteSubmitFormRequest extends DungeonRouteSubmitFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    #[Override]
    public function rules(): array
    {
        $rules = parent::rules();
        // Cannot change these two once edited
        unset($rules['dungeon_id']);
        unset($rules['teeming']);

        return $rules;
    }
}
