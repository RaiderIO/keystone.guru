<?php

namespace App\Http\Requests\KillZone;

use Illuminate\Foundation\Http\FormRequest;

class StoreFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id'       => 'int',
            'floor_id' => 'nullable|int',
            'color'    => 'string',
            'lat'      => 'nullable|numeric',
            'lng'      => 'nullable|numeric',
            'index'    => 'int',
            'enemies'  => 'array',
        ];
    }
}
