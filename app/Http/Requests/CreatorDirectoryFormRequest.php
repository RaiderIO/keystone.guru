<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatorDirectoryFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // Usernames are capped at 24 characters, so anything longer cannot match a creator
            'search' => [
                'nullable',
                'string',
                'max:24',
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'search.max' => __('validation.custom.creator_search.max'),
        ];
    }

    /**
     * The trimmed search term, or null when the user is browsing unfiltered.
     */
    public function search(): ?string
    {
        return once(function (): ?string {
            $search = $this->validated('search');

            if ($search === null) {
                return null;
            }

            $search = trim((string)$search);

            return $search === '' ? null : $search;
        });
    }
}
