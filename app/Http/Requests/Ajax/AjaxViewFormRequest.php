<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AjaxViewFormRequest extends FormRequest
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
            // I'm not 100% sure if this is a security risk or not, but let's whitelist the views we want to use
            'view' => [
                'required',
                'string',
                Rule::in(['common.modal.createroute']),
            ],
        ];
    }

    #[\Override]
    public function validationData(): array
    {
        // Merge route params into the data being validated
        return array_merge($this->all(), [
            'view' => $this->route('view'),
        ]);
    }
}
