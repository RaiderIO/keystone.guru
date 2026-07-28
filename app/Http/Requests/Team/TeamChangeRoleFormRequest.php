<?php

namespace App\Http\Requests\Team;

use App\Models\TeamUser;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * User wants to change the role of another user in the team
 */
class TeamChangeRoleFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Rule>|string|Rule>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'exists:users,name'],
            'role'     => ['required', 'string', Rule::in(array_keys(TeamUser::ALL_ROLES))],
        ];
    }

    public function targetUser(): User
    {
        return once(fn() => User::where('name', $this->validated('username'))->firstOrFail());
    }
}
