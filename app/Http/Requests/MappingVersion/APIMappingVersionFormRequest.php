<?php

namespace App\Http\Requests\MappingVersion;

use Illuminate\Foundation\Http\FormRequest;

class APIMappingVersionFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'enemy_forces_required'           => 'int',
            'enemy_forces_required_teeming'   => 'int|nullable',
            'enemy_forces_shrouded'           => 'int|nullable',
            'enemy_forces_shrouded_zul_gamux' => 'int|nullable',
            'timer_max_seconds'               => 'int',
            'facade_enabled'                  => 'nullable|boolean',
        ];
    }
}
