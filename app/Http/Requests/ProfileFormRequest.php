<?php

namespace App\Http\Requests;

use App\Models\Laratrust\Role;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->hasRole(Role::ROLE_ALL) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'avatar'           => 'image|mimes:png|max:256',
            'name'             => ['required|alpha_dash|min:3|max:24', Rule::unique('users')->ignore($this->route()->parameter('user'))],
            'email'            => 'required|email|unique:users',
            'echo_color'       => 'required|color',
            'current_password' => 'min:8',
            'new_password'     => 'min:8|confirmed',
        ];
    }
}
