<?php

namespace App\Http\Requests;

use App\Models\Laratrust\Role;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpansionFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                Rule::unique('expansions')->ignore($this->route()->parameter('expansion')),
            ],
            'shortname' => [
                'required',
                Rule::unique('expansions')->ignore($this->route()->parameter('expansion')),
            ],
            'color' => 'required',
        ];
    }
}
