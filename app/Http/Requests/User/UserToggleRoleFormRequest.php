<?php

namespace App\Http\Requests\User;

use App\Models\Laratrust\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserToggleRoleFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The route already sits behind the admin middleware group, and the controller authorizes the
     * role change itself through UserPolicy::makeRole.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::exists(Role::class, 'name')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'role.exists' => __('validation.custom.role.exists'),
        ];
    }

    public function role(): Role
    {
        return once(fn() => Role::query()->where('name', $this->validated('role'))->firstOrFail());
    }
}
