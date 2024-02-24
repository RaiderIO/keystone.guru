<?php

namespace App\Http\Requests\Npc;

use Auth;
use Illuminate\Foundation\Http\FormRequest;

class NpcEnemyForcesFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return Auth::user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'enemy_forces'         => 'required|int',
            'enemy_forces_teeming' => 'nullable|int',
        ];
    }
}
