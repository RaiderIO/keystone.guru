<?php

namespace App\Http\Requests;

class APIDungeonRouteFormRequest extends DungeonRouteFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = parent::rules();
        // Cannot change these two once edited
        unset($rules['dungeon_id']);
        unset($rules['teeming']);

        return $rules;
    }
}
