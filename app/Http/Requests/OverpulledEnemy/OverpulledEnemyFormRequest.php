<?php

namespace App\Http\Requests\OverpulledEnemy;

use Illuminate\Foundation\Http\FormRequest;

class OverpulledEnemyFormRequest extends FormRequest
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
            'kill_zone_id' => 'required|int',
            'enemy_ids'    => 'required|array',
            'enemy_ids.*'  => 'numeric',
        ];
    }
}
