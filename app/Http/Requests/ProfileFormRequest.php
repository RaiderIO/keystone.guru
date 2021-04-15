<?php

namespace App\Http\Requests;

use App\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var User $user */
        $user = \Auth::user();
        return $user->hasRole("user") || $user->hasRole("admin");
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'avatar' => 'image|mimes:png|max:256',
            'name' => ['required|alpha_dash|min:3|max:24', Rule::unique('users')->ignore($this->route()->parameter('user'))],
            'email' => 'required|email|unique:users',
            'echo_color' => 'required|color',
            'current_password' => 'min:8',
            'new_password' => 'min:8|confirmed',
        ];
    }
}
