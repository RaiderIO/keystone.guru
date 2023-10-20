<?php

namespace App\Http\Requests\KillZone;

use Illuminate\Foundation\Http\FormRequest;

class APIKillZoneMassFormRequest extends FormRequest
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
            'killzones'       => 'array',
            'killzones.*.id'    => 'int',
            'killzones.*.color' => [
                'required',
                'regex:/^#([a-f0-9]{6}|[a-f0-9]{3})$/i',
            ],
            'killzones.*.index' => 'int',
        ];
    }
}
