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
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'kill_zone_id' => 'required|int',
            'enemy_ids' => 'required|array',
            'enemy_ids.*' => 'numeric',
            'no_result' => 'nullable|bool',
        ];
    }
}
