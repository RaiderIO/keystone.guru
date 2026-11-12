<?php

namespace App\Http\Requests;

use App\Models\Laratrust\Role;
use App\Models\User;
use App\Service\User\ApiTokenScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileApiTokenFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:100'],
            'scopes'   => ['required', 'array', 'min:1'],
            'scopes.*' => ['required', 'string', 'distinct', Rule::in(self::selectableScopes(Auth::user()))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'scopes.required' => __('validation.custom.profile_api_token_scopes.required'),
            'scopes.min'      => __('validation.custom.profile_api_token_scopes.required'),
            'scopes.*.in'     => __('validation.custom.profile_api_token_scopes.not_allowed'),
        ];
    }

    /**
     * The admin scope only does anything for an account whose role passes the admin endpoints' role gate, so it is
     * not offered to anyone else.
     *
     * @return array<int, string>
     */
    public static function selectableScopes(?User $user): array
    {
        $canUseAdminEndpoints = $user?->hasRole([Role::ROLE_ADMIN, Role::ROLE_AI_AGENT]) ?? false;

        $result = [];
        foreach (ApiTokenScope::cases() as $scope) {
            if ($scope !== ApiTokenScope::Admin || $canUseAdminEndpoints) {
                $result[] = $scope->value;
            }
        }

        return $result;
    }
}
