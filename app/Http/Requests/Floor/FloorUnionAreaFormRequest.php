<?php

namespace App\Http\Requests\Floor;

use App\Models\Floor\FloorUnion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FloorUnionAreaFormRequest extends FormRequest
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
            'id'             => 'required:int',
            'floor_union_id' => ['required', Rule::exists(FloorUnion::class, 'id')],
            'vertices'       => 'required:array',
        ];
    }
}
