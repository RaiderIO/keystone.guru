<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GrantPatreonBenefitsFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The route already sits behind the admin middleware group; the grant itself is additionally
     * gated by the makeRole-style Gate check in the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'reason' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'reason.required' => __('validation.custom.patreon_grant_reason.required'),
            'reason.max'      => __('validation.custom.patreon_grant_reason.max'),
        ];
    }

    public function reason(): string
    {
        return trim((string)$this->validated('reason'));
    }
}
