<?php

namespace App\Http\Requests\Ajax;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class AjaxViewFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
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

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function validationData(): array
    {
        // Merge route params into the data being validated
        return array_merge($this->all(), [
            'view' => $this->route('view'),
        ]);
    }
}
