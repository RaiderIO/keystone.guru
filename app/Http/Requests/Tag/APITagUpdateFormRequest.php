<?php

namespace App\Http\Requests\Tag;

use App\Models\Laratrust\Role;
use Auth;
use Illuminate\Foundation\Http\FormRequest;

class APITagUpdateFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->hasRole(Role::ROLE_ALL);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name'  => 'required|string',
            'color' => 'required|string',
        ];
    }
}
