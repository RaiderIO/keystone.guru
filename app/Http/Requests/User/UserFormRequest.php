<?php

namespace App\Http\Requests\User;

use App\User;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'map_facade_style' => ['nullable', Rule::in(User::MAP_FACADE_STYLE_ALL)],
        ];
    }
}
