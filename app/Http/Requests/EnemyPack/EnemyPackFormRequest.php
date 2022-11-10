<?php

namespace App\Http\Requests\EnemyPack;

use App\Models\Faction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnemyPackFormRequest extends FormRequest
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
            'id'             => 'int',
            'floor_id'       => 'int',
            'color'          => 'string',
            'color_animated' => 'nullable|string',
            'teeming'        => 'nullable|string',
            'faction'        => [Rule::in(array_merge(array_keys(Faction::ALL), ['any']))],
            'label'          => 'string',
            'vertices'       => 'array',
        ];
    }
}
