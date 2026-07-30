<?php

namespace App\Http\Requests;

use App\Models\Affix;
use App\Models\Laratrust\Role;
use Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AffixFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        return [
            'key' => [
                'required',
                Rule::unique(Affix::class, 'key')->ignore($this->route()->parameter('affix')),
            ],
            'name'        => 'required|string',
            'description' => 'required|string',
            'affix_id'    => 'required|integer|min:0',
        ];
    }
}
