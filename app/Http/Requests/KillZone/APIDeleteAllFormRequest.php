<?php

namespace App\Http\Requests\KillZone;

use Illuminate\Foundation\Http\FormRequest;

class APIDeleteAllFormRequest extends FormRequest
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
            'confirm' => [
                'required',
                'in:yes',
            ],
        ];
    }
}
