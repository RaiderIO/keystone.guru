<?php

namespace App\Http\Requests\Npc;

use Auth;
use Illuminate\Foundation\Http\FormRequest;

class NpcEnemyForcesFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'enemy_forces' => 'required|int',
            'enemy_forces_teeming' => 'nullable|int',
        ];
    }
}
