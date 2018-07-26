<?php

namespace App\Http\Requests;

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
        return \Auth::user()->hasRole("user") || \Auth::user()->hasRole("admin");
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required|alpha_dash|min:3|max:24', Rule::unique('users')->ignore($this->route()->parameter('user'))],
            'email' => 'required|email|unique:users',
            'current_password' => 'min:6',
            'new_password' => 'min:6|confirmed'
        ];
    }
}
