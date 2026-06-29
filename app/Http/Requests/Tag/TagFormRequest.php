<?php

namespace App\Http\Requests\Tag;

use Illuminate\Foundation\Http\FormRequest;

class TagFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>|string> */
    public function rules(): array
    {
        return [
            // Bit strange - but required with multiple forms existing on the profile page
            'tag_name_new' => ['required'],
        ];
    }
}
