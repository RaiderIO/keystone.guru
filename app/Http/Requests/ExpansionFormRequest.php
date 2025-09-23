<?php

namespace App\Http\Requests;

use App\Models\Expansion;
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
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        /** @var Expansion $expansion */
        $expansion = $this->route()->parameter('expansion');
        $rules     = [
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
        // Icon is required when making a new expansion, when editing it's optional
        if ($expansion === null) {
            $rules['icon'] = 'required|image|mimes:png|max:128';
        }

        return $rules;
    }
}
