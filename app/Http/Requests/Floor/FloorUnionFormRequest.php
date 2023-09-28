<?php

namespace App\Http\Requests\Floor;

use App\Models\Floor\Floor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FloorUnionFormRequest extends FormRequest
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
            'id'              => 'required:int',
            'floor_id'        => ['required', Rule::exists(Floor::class, 'id')],
            'target_floor_id' => ['required', Rule::exists(Floor::class, 'id')],
            'lat'             => 'float',
            'lng'             => 'float',
            'size'            => 'float',
            'rotation'        => 'float',
        ];
    }
}
