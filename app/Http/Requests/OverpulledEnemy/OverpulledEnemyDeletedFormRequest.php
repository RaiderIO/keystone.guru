<?php

namespace App\Http\Requests\OverpulledEnemy;

use Illuminate\Foundation\Http\FormRequest;

class OverpulledEnemyDeletedFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            'enemy_ids'   => 'required|array',
            'enemy_ids.*' => 'numeric',
            'no_result'   => 'nullable|int',
        ];
    }
}
