<?php

namespace App\Http\Requests;

use App\Models\PublishedState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * User wants to publish their route
 *
 * @package App\Http\Requests
 */
class PublishFormRequest extends FormRequest
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
            'published_state' => ['required', Rule::in(array_keys(PublishedState::ALL))],
        ];
    }
}
