<?php

namespace App\Http\Requests\MountableArea;

use App\Models\Faction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MountableAreaFormRequest extends FormRequest
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
            'id'       => 'required:int',
            'floor_id' => 'required:int',
            'vertices' => 'required:array',
        ];
    }
}
