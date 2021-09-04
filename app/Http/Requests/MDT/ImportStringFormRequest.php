<?php

namespace App\Http\Requests\MDT;

use Illuminate\Foundation\Http\FormRequest;

class ImportStringFormRequest extends FormRequest
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
            'import_string'      => 'required|string',
            'mdt_import_sandbox' => 'bool',
        ];
    }
}
