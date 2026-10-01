<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserPatreonBenefitsFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The route already sits behind the admin middleware group.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            // jQuery leaves the key out entirely when no benefit is selected
            'patreonBenefits' => [
                'nullable',
                'array',
            ],
            'patreonBenefits.*' => [
                'integer',
                'distinct',
                'exists:patreon_benefits,id',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'patreonBenefits.*.exists' => __('validation.custom.patreon_benefits.exists'),
        ];
    }

    /**
     * @return array<int, int>
     */
    public function patreonBenefitIds(): array
    {
        return array_map(intval(...), $this->validated('patreonBenefits') ?? []);
    }
}
