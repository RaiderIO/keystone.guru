<?php

namespace App\Http\Requests;

use App\Models\Laratrust\Role;
use App\Models\PublishedState;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminDungeonRouteFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'published_state_id' => [
                'required',
                'integer',
                Rule::in(array_values(PublishedState::ALL)),
            ],
        ];
    }
}
