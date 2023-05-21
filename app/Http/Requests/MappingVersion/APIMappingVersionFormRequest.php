<?php

namespace App\Http\Requests\MappingVersion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @package App\Http\Requests
 */
class APIMappingVersionFormRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'enemy_forces_required'           => 'int',
            'enemy_forces_required_teeming'   => 'int|nullable',
            'enemy_forces_shrouded'           => 'int|nullable',
            'enemy_forces_shrouded_zul_gamux' => 'int|nullable',
            'timer_max_seconds'               => 'int',
        ];
    }
}
