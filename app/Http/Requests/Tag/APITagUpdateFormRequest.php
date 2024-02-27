<?php

namespace App\Http\Requests\Tag;

use Auth;
use Illuminate\Foundation\Http\FormRequest;

class APITagUpdateFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole('user') || Auth::user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'color' => 'required|string',
        ];
    }
}
