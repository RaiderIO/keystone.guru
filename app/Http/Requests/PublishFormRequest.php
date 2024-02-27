<?php

namespace App\Http\Requests;

use App\Models\PublishedState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * User wants to publish their route
 */
class PublishFormRequest extends FormRequest
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
            'published_state' => ['required', Rule::in(array_keys(PublishedState::ALL))],
        ];
    }
}
