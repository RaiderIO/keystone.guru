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
        return true; // Auth::user()->hasRole(["user", "admin"]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'published_state' => ['required', Rule::in(PublishedState::ALL)]
        ];
    }
}
