<?php

namespace App\Http\Requests;

use App\Models\Laratrust\Role;
use App\Rules\BannedIpRangeRule;
use Auth;
use Illuminate\Foundation\Http\FormRequest;

class AdminToolsBannedIpAddressStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasRole(Role::ROLE_ADMIN);
    }

    /** @return array<string, array<int, object|string>|string> */
    public function rules(): array
    {
        return [
            'ip_address' => ['required', 'string', 'max:45', new BannedIpRangeRule($this->ip())],
            'reason'     => ['nullable', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
